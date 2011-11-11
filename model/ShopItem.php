<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<?php

class foo
{
    public $_bar;
    public function  __construct(& $bar)
    {
        $this->_bar = & $bar;
    }

    function do_your_thing()
    {
        $temp = array(
            'One' => 1,
            'Two' => 2
        );

        $this->_bar[] = $temp;

        echo('from Do_your_thing: ');
        print_r($this->_bar);   // ---------------- [1]
    }
}

$abc = array();
$var = new foo($abc);
$var->do_your_thing();

echo('from main [local variable]: ');
print_r($abc);   // ---------------- [2]

echo('from main: [object member variable] ');
print_r($var->_bar);    // ---------------- [3]
?>

</html>