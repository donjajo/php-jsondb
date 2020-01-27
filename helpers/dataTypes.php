<?php 
/**
 * Converts objects to array
 * 
 * @param object $obj object(s)
 * 
 * @return array 
 */
function obj_to_array( $obj ) {
    // Not an array or object? Return back what was given
    if( !is_array( $obj ) && !is_object( $obj ) ) 
        return $obj;

    $arr = (array) $obj;

    foreach( $arr as $key => $value ) {
        $arr[ $key ] = obj_to_array( $value );
    }

    return $arr;
}