<?php defined('SYSPATH') OR die('No direct script access.');

class Controller_Media extends Controller
{
    public function action_index()
    {
        // Get the file path from the request
        $file = $this->request->param('file');

        // Find the file extension
        $ext = pathinfo($file, PATHINFO_EXTENSION);

        // Remove the extension from the filename
        $file = substr($file, 0, -(strlen($ext) + 1));

        // Find the file in 'static/' dirs across the cascading file system
        $filename = Kohana::find_file('static/', $file, $ext);

        if ($filename) {
            // Set the max-age for the file
            $max_age = 60 * 60 * 24 * 30;
            $filemtime = gmdate('r', filemtime($filename));
            // Set the etag to compare
            $etag = md5($filename . $filemtime);
            // Get the etag
            $match_etag = $this->request->headers('if-none-match');
            // Get the filemtime
            $match_filemtime = $this->request->headers('if-modified-since');

            if ($filemtime == $match_filemtime || $etag == $match_etag) {
                $this->response->status(304);

                return;
            }

            // Get the file content and deliver it
            $this->response->body(file_get_contents($filename));

            // Set the proper headers to allow caching
            $this->response->headers('Content-Type', File::mime_by_ext($ext));
            $this->response->headers('Content-Length', filesize($filename));
            $this->response->headers('Last-Modified', $filemtime);
            $this->response->headers('Expires', gmdate('r', time() + $max_age));
            $this->response->headers('Cache-Control', 'max-age=' . $max_age . ', public');
            $this->response->headers('Etag', $etag);
        } else {
            $this->response->status(404);
            // Throw some suitable exception here
        }
    }
}
