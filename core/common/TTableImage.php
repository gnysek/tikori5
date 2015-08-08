<?php

class TTableImage
{

    public function render($data, $value, $options)
    {
        if (!empty($value)) {

            $options = array('alt' => '[image]', 'src' => $value) + $options;
            foreach ($options as $k => $v) {
                $attr[] = $k . '="' . $v . '"';
            }
            return '<img ' . implode(' ', $attr) . '/>';
        }
        return '';
    }
}
