<?php
namespace YFW\Library;

class FileHandler
{
    // Potentially unsafe to allow these to be changed from scripts. Don't do that.
    const NICE_VALUE = 19;
    const PNGCRUSH_OPTIONS = '-reduce -fix -rem allb -l 9';
    const IMAGICK_FILTER = 'triangle';

    public static function getVideoMeta(string $file): ?\stdClass
    {
        $probe = shell_exec('nice --adjustment=19 ffprobe -show_streams -of json ' . escapeshellarg($file) . ' -v quiet');
        $videoInfo = json_decode($probe);

        if (empty($videoInfo->streams) || count($videoInfo->streams) == 0) {
            return null;
        }

        $videoInfo = $videoInfo->streams;

        $video = new \stdClass();
        $video->hasSound = false;
        $video->width = null;
        $video->height = null;
        $video->audioOnly = false;

        // Figure out which stream to use info from and if the file has sound
        foreach ($videoInfo AS $key => $stream) {
            if ($stream->codec_type == 'video' && empty($streamNum)) {
                $streamNum = $key;
            } elseif ($stream->codec_type == 'audio') {
                $video->hasSound = true;
                if (isset($videoInfo[$key]->duration)) {
                    $audioDuration = $videoInfo[$key]->duration;
                }
            }
        }

        if (!isset($streamNum)) {
            // No video found
            if ($video->hasSound && isset($audioDuration)) {
                // This is an audio only file, like MP3 without images
                $video->duration = $audioDuration;
                $video->audioOnly = true;

                return $video;
            }

            return null;
        }

        if (isset($videoInfo[$streamNum]->duration)) {
            $video->duration = (int)$videoInfo[$streamNum]->duration;
        } else {
            $video->duration = null;
        }

        if (!empty($videoInfo[$streamNum]->width) && !empty($videoInfo[$streamNum]->height)) {
            $video->width = (int)$videoInfo[$streamNum]->width;
            $video->height = (int)$videoInfo[$streamNum]->height;
        }

        return $video;
    }

    public static function convertVideo(string $file): bool
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'vid') . '.mp4';

        system('nice --adjustment=19 ffmpeg -i ' . escapeshellarg($file) . ' -threads 0 -strict -2 '
            . '-c:v libx264 -pix_fmt yuv420p -crf 23 -maxrate 3000k -bufsize 9000k -preset:v veryfast -profile:v high '
            . '-level:v 4.1 -filter_complex scale="trunc(in_w/2)*2:trunc(in_h/2)*2" -movflags faststart -c:a aac '
            . '-ac 2 -ar 44100 -b:a 128k ' . escapeshellarg($tmpFile));

        if (!static::verifyFile($tmpFile)) {
            return false;
        }

        // If the file gets deleted before we finish
        if (!is_file($file)) {
            unlink($tmpFile);

            return false;
        }

        unlink($file);
        rename($tmpFile, $file);

        return is_file($file) !== false;
    }

    public static function convertAudio(string $file): bool
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'aud') . '.m4a';

        system('nice --adjustment=19 ffmpeg -i ' . escapeshellarg($file) . ' -threads 0 -strict -2 -c:a aac '
            . '-ac 2 -ar 44100 -b:a 128k ' . escapeshellarg($tmpFile));

        if (!static::verifyFile($tmpFile)) {
            return false;
        }

        // If the file gets deleted before we finish
        if (!is_file($file)) {
            unlink($tmpFile);

            return false;
        }

        unlink($file);
        rename($tmpFile, $file);

        return is_file($file) !== false;
    }

    public static function createThumbnail(
        string $file,
        string $destination,
        int $maxWidth,
        int $maxHeight,
        string $outFormat
    ): bool {
        return static::createImage($file, $destination, $maxWidth, $maxHeight, $outFormat, true);
    }

    public static function createImage(
        string $file,
        string $destination,
        int $maxWidth,
        int $maxHeight,
        string $outFormat,
        bool $thumbnail = false
    ): bool {
        set_time_limit(60);

        if (is_file($destination)) {
            return false;
        }

        $cmd = 'nice --adjustment=' . (int)static::NICE_VALUE . ' convert';

        // Set limits
        $cmd .= ' -limit area 512MiB -limit memory 128MiB -limit map 256MiB - limit disk 1GiB -limit time 60';

        // Input file
        $cmd .= ' ' . escapeshellarg($file);

        // Keep only the first frame
        $cmd .= '[0]';

        // Reset virtual canvas
        $cmd .= ' +repage';

        // Set filter
        $cmd .= ' -filter ' . escapeshellarg(static::IMAGICK_FILTER);

        // Resize larger than maxSizes
        $cmd .= !$thumbnail ? ' -resize ' : ' -thumbnail ';
        $cmd .= (int)$maxWidth . 'x' . (int)$maxHeight . '\>';

        // Rotate by EXIF rotation tag
        $cmd .= ' -auto-orient';

        // Set quality
        $cmd .= ' -quality 80';

        // Strip color profiles, comments, etc.
        $cmd .= ' -strip';

        // Flatten
        $cmd .= ' -flatten';

        // Output file
        $cmd .= ' ' . escapeshellarg($outFormat . ':' . $destination);

        shell_exec($cmd);

        if (filesize($destination) == 0) {
            unlink($destination);
        }

        return is_file($destination) !== false;
    }

    public static function pngCrush(string $file): bool
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'img') . '.png';

        $cmd = 'nice --adjustment=' . (int)static::NICE_VALUE . ' pngcrush ';
        $cmd .= static::PNGCRUSH_OPTIONS . ' ' . escapeshellarg($file) . ' ' . escapeshellarg($tmpFile);

        shell_exec($cmd);

        if (!static::verifyFile($tmpFile)) {
            return false;
        }

        // If the file gets deleted before we finish
        if (!is_file($file)) {
            unlink($tmpFile);

            return false;
        }

        unlink($file);
        rename($tmpFile, $file);

        return is_file($file) !== false;
    }

    public static function getGifFrameCount(string $file): int
    {
        $cmd = 'echo -n `nice --adjustment=' . (int)static::NICE_VALUE . ' identify ';
        $cmd .= escapeshellarg($file) . ' | wc -l`';
        $frames = shell_exec($cmd);

        return (int)$frames;
    }

    public static function verifyFile(string $file): bool
    {
        if (!is_file($file)) {
            return false;
        }

        if (filesize($file) === 0) {
            unlink($file);

            return false;
        }

        return true;
    }
}
