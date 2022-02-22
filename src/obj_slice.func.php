<?php
    function obj_slice(&$object, $keys) {
        //
        $data = (object)[];
        //
        if (is_array($keys))
            //
            foreach ($keys as $key) {
                //
                $data->{$key} = $object->{$key};
                //
                unset($object->{$key});
            }
        else {
            //
            $data->{$keys} = $object->{$keys};
            //
            unset($object->{$keys});
        }
        //
        return is_array($keys) ? $data : $data->{$keys};
    }