<?php
class Download {
    private $file;
    private $data;
    private $name;
    private $boundary;
    private $size = 0;
    private $from_memory = false;
    private $pointer = 0;

    /**
     * Constructor can accept either a file path or direct data.
     *
     * @param string $file_or_data File path or data string.
     * @param string|null $name Name of the file if passing data directly.
     */
    public function __construct( $file_or_data, $name = null ) {
        if ( ! is_null( $name ) && is_string( $file_or_data ) ) {
            // Data has been directly passed.
            $this->from_memory = true;
            $this->data = $file_or_data;
            $this->size = strlen( $file_or_data );
            $this->name = basename( $name );
            $this->boundary = md5( $name );
        } else {
            // Assume file path is given.
            if ( ! is_file( $file_or_data ) ) {
                header( 'HTTP/1.1 400 Invalid Request' );
                die( '<h3>File Not Found</h3>' );
            }

            $this->size = filesize( $file_or_data );
            $this->file = fopen( $file_or_data, 'r' );
            $this->boundary = md5( $file_or_data );
            $this->name = basename( $file_or_data );
        }
    }

    /**
     * Process the download request.
     */
    public function process() {
        $ranges = null;
        $t = 0;
        if ( $_SERVER['REQUEST_METHOD'] === 'GET' && isset( $_SERVER['HTTP_RANGE'] ) && $range = stristr( trim( $_SERVER['HTTP_RANGE'] ), 'bytes=' ) ) {
            $range = substr( $range, 6 );
            $ranges = explode( ',', $range );
            $t = count( $ranges );
        }

        header( 'Accept-Ranges: bytes' );
        header( 'Content-Type: application/octet-stream' );
        header( 'Content-Transfer-Encoding: binary' );
        header( sprintf( 'Content-Disposition: attachment; filename="%s"', $this->name ) );

        if ( $t > 0 ) {
            header( 'HTTP/1.1 206 Partial content' );
            $t === 1 ? $this->pushSingle( $range ) : $this->pushMulti( $ranges );
        } else {
            header( 'Content-Length: ' . $this->size );
            $this->readFile();
        }

        flush();
    }

    /**
     * Push a single range of data.
     *
     * @param string $range The range string.
     */
    private function pushSingle( $range ) {
        $start = 0;
        $end = 0;
        $this->getRange( $range, $start, $end );
        header( 'Content-Length: ' . ( $end - $start + 1 ) );
        header( sprintf( 'Content-Range: bytes %d-%d/%d', $start, $end, $this->size ) );
        $this->seek( $start );
        $this->readBuffer( $end - $start + 1 );
        $this->readFile();
    }

    /**
     * Push multiple ranges of data.
     *
     * @param array $ranges The ranges array.
     */
    private function pushMulti( $ranges ) {
        $length = 0;
        $start = 0;
        $end = 0;
        $output = '';

        $tl = "Content-type: application/octet-stream\r\n";
        $formatRange = "Content-range: bytes %d-%d/%d\r\n\r\n";

        foreach ( $ranges as $range ) {
            $this->getRange( $range, $start, $end );
            $length += strlen( "\r\n--$this->boundary\r\n" );
            $length += strlen( $tl );
            $length += strlen( sprintf( $formatRange, $start, $end, $this->size ) );
            $length += ( $end - $start + 1 );
        }
        $length += strlen( "\r\n--$this->boundary--\r\n" );
        header( "Content-Length: $length" );
        header( "Content-Type: multipart/x-byteranges; boundary=$this->boundary" );
        foreach ( $ranges as $range ) {
            $this->getRange( $range, $start, $end );
            echo "\r\n--$this->boundary\r\n";
            echo $tl;
            echo sprintf( $formatRange, $start, $end, $this->size );
            $this->seek( $start );
            $this->readBuffer( $end - $start + 1 );
        }
        echo "\r\n--$this->boundary--\r\n";
    }

    /**
     * Parse and validate the requested range.
     *
     * @param string $range The range string.
     * @param int    $start The start position.
     * @param int    $end The end position.
     */
    private function getRange( $range, &$start, &$end ) {
        list( $start, $end ) = explode( '-', $range );

        $fileSize = $this->size;
        if ( $start === '' ) {
            $tmp = $end;
            $end = $fileSize - 1;
            $start = $fileSize - $tmp;
            if ( $start < 0 ) {
                $start = 0;
            }
        } else {
            if ( $end === '' || $end > $fileSize - 1 ) {
                $end = $fileSize - 1;
            }
        }

        if ( $start > $end ) {
            header( 'Status: 416 Requested range not satisfiable' );
            header( 'Content-Range: */' . $fileSize );
            exit();
        }

        return array( $start, $end );
    }

    /**
     * Read the entire file or data and output it.
     */
    private function readFile() {
        if ( $this->from_memory ) {
            // Reading from memory.
            while ( $this->pointer < $this->size ) {
                echo $this->subData( $this->pointer, 1024 );
                $this->pointer += 1024;
                flush();
            }
        } else {
            // Reading from file.
            while ( ! feof( $this->file ) ) {
                echo fgets( $this->file );
                flush();
            }
        }
    }

    /**
     * Read a specified number of bytes from the file or data buffer.
     *
     * @param int $bytes Number of bytes to read.
     * @param int $size Buffer size for reading.
     */
    private function readBuffer( $bytes, $size = 1024 ) {
        $bytesLeft = $bytes;
        while ( $bytesLeft > 0 ) {
            $bytesRead = ( $bytesLeft > $size ) ? $size : $bytesLeft;
            if ( $this->from_memory ) {
                echo $this->subData( $this->pointer, $bytesRead );
                $this->pointer += $bytesRead;
            } else {
                echo fread( $this->file, $bytesRead );
            }
            flush();
            $bytesLeft -= $bytesRead;
        }
    }

    /**
     * Set the pointer position for reading.
     *
     * @param int $pos The position to seek to.
     */
    private function seek( $pos ) {
        if ( $this->from_memory ) {
            $this->pointer = $pos;
        } else {
            fseek( $this->file, $pos );
        }
    }

    /**
     * Extract a substring from the data buffer.
     *
     * @param int $start Starting position.
     * @param int $length Number of bytes to extract.
     * @return string Substring of data.
     */
    private function subData( $start, $length ) {
        return substr( $this->data, $start, $length );
    }
}
