<?php
    function str_trim($text, $length, $words = true) {
        if (strlen($text) <= $length) return $text; // no-op
        // if not whole words, simply truncate to length
        if (!$words) return substr($text, 0, $length).' …';
        // truncate to length+1 (to check if last word is whole)
        $text = substr($text, 0, $length+1);
        // remove trailing truncated word to leave whole words only
        $m = preg_match('/(.+)\b\w+$/', $text, $match);
        $text = $m ? $match[1] : $text;
        // remove any trailing whitespace/punctuation
        // (or trailing extra character if there was only one word)
        $m = preg_match('/(.+)\b\W+$/', $text, $match);
        $text = $m ? $match[1] : substr($text, 0, $length);
        return $text.' …';
    }