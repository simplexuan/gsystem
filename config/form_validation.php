<?php
$config = array(
        'id_allocate/allocate' => array(
            array(
                'field' => 'c[id_allocate][ns]',
                'label' => 'ns',
                'rules' => 'required|min_length[2]|max_length[10]',
                ),
            array(
                'field' => 'c[id_allocate][table_name]',
                'label' => 'table_name',
                'rules' => 'required',
                ),
            ),
        );
