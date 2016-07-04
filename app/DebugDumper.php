<?php

namespace App;

trait DebugDumper
{
    /**
     * @param $debugdata
     * @param string $file
     * @param int $line
     */
    public function dl($debugdata, $file = __FILE__, $line = __LINE__)
    {
        //        r(['DebugLog: ' . basename($file) . ':' . $line => $debugdata]);
        if (is_string($debugdata)) {
            r(['DebugLog' => $debugdata]);
        } else {
            r(['DebugLog' => '']);
            r($debugdata);
        }
    }
}
