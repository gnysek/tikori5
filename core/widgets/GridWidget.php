<?php

class GridWidget extends Widget
{

    public $columns = array();
    public $data = array();
    public $titles = array();
    public $renderer = array();
    public $options = array();

    public function onCall()
    {

        $html = '';

        // render TH headers
        foreach ($this->columns as $v) {
            if (array_key_exists($v, $this->titles)) {
                $v = $this->titles[$v];
            } else {
                $v = ucfirst($v);
            }
            $html .= Html::htmlTag('th', null, $v) . PHP_EOL;
        }

        if (!empty($this->options)) {
            $html .= Html::htmlTag('th', null, 'Options') . PHP_EOL;
        }

        $html = Html::htmlTag('tr', null, PHP_EOL . $html) . PHP_EOL;

        // render rows
        foreach ($this->data as $record) {
            if ($record instanceof TModel) {
                $th = '';
                //render each column for current row
                foreach ($this->columns as $column) {
                    $value = $record->$column;

                    // custom renderer
                    if (array_key_exists($column, $this->renderer)) {
                        $renderer = $this->renderer[$column];
                        if (is_callable(array('Renderer', $renderer))) {
                            $value = call_user_func(array('Renderer', $renderer), $value, $record);
                        } elseif (is_callable($renderer)) {
                            $value = $renderer($value, $record);
                        }
                    }

                    $th .= Html::htmlTag('td', null, $value) . PHP_EOL;
                }

                // options
                if (!empty($this->options)) {
                    $options = array();
                    foreach ($this->options as $k => $v) {
                        if (!empty($v['url'])) {
                            // convert :param to value of that param
                            $url = $v['url'];
                            foreach ($url as $urlElem => $urlParam) {
                                if ($urlElem === 0) {
                                    continue;
                                }
                                if ($urlParam{0} === ':') {
                                    $urlParam = str_replace(':', '', $urlParam);
                                    $url[$urlElem] = $record->$urlParam;
                                }
                            }
                            // render option link
                            $options[] = Html::link(
                                $k{0}, $url, ((!empty($v['options'])) ? $v['options'] : array()) + array('title' => $k)
                            );
                        }
                    }
                    $th .= Html::htmlTag('td', null, implode(' | ', $options)) . PHP_EOL;
                }

                $html .= Html::htmlTag('tr', null, PHP_EOL . $th) . PHP_EOL;
            }
        }


        $html = Html::htmlTag('table', array('width' => '100%'), PHP_EOL . $html);

//        var_dump($html);

        echo $html;
    }
}
