# ModuleParser

**ModuleParser** - класс для анализа PHP файлов на классы, функции, интерфейсы и трейты. С помощью этого класса можно узнать позиции начала и конца синтаксических структур, их названия и описания. Так же поддерживаются лямбда-функции и классы

## Установка
```cmd
php qero.phar i KRypt0nn/ModuleParser
```

```php
<?php

require 'qero-packages/autoload.php';
```

[Что такое Qero?](https://github.com/KRypt0nn/Qero)

<center>или</center>

Скачайте файл `ModuleParser.php` и подключите его к проекту с помощью *require*

```php
<?php

require 'ModuleParser.php';
```

## Пример работы

```php
<?php

require 'qero-packages/autoload.php';

use ModuleParser\ModuleParser;

function printStructure ($modules, $deep = 0)
{
    foreach ($modules as $module)
    {
        echo str_repeat (' ', $deep) .' | '. $module->description . PHP_EOL;

        if (sizeof ($module->subitems) > 0)
        {
            echo str_repeat (' ', $deep) .' +---+'. PHP_EOL;

            printStructure ($module->subitems, $deep + 4);

            echo str_repeat (' ', $deep) .' +---+'. PHP_EOL;
        }
    }
}

printStructure (ModuleParser::parse ('example.php'));
```

К примеру, `example.php` (где я понабирал этот ужас можете не спрашивать):

```php
<?php

final class TMultiButton extends TLabel
{
	public $class_name_ex = __CLASS__;
	function __construct($onwer = nil, $init = true, $self = nil)
	{
		parent::__construct($onwer, $init, $self);
		$this->color       = $this->colorOne;
		$this->font->color = $this->FontColorOne;
		$this->alignment   = $this->hAlign;
		$this->autoSize    = false;
		$this->layout      = $this->vAlign;
	}
	public function __initComponentInfo()
	{
		$this->alignment = $this->hAlign;
		$this->autoSize  = false;
		$this->layout    = $this->vAlign;
		$obj             = $this;
		$this->onMouseLeave = function() use ($obj)
		{
			$obj->color       = $obj->ColorOne;
			$obj->font->color = $obj->FontColorOne;
		};
		$this->onMouseEnter = function() use ($obj)
		{
			$obj->color       = $obj->ColorTwo;
			$obj->font->color = $obj->FontColorTwo;
		};
		$this->onMouseDown = function() use ($obj)
		{
			$obj->color       = $obj->ColorThree;
			$obj->font->color = $obj->FontColorThree;
		};
		$this->onMouseUp = function() use ($obj)
		{
			$obj->color       = $obj->ColorTwo;
			$obj->font->color = $obj->FontColorTwo;
		};
	}
}

$test = function () {};

$test = (new class { public static function test () { (function () {(new class { public static function test () { (function () {}) (); } })::test ();}) (); } })::test ();

function test1 () {}

interface test2
{
	public static function test ($a, $b, $c);
}

trait example{public static function test ($a, $b, $c){}}

// function test1 () {}
# function test2 () {}

/*
	function test3 () {}
*/

/**
 * function test4 () {}
 */

$test = <<<						TEST1

function test5 () {}

TEST1;

$test = <<<				'TEST2'

function test6 () {}

TEST2;
```

преобразуется в

```
 | final class TMultiButton extends TLabel
 +---+
     | function __construct ($onwer = nil, $init = true, $self = nil)
     | public function __initComponentInfo ()
     +---+
         | function ()
         | function ()
         | function ()
         | function ()
     +---+
 +---+
 | function ()
 | new class
 +---+
     | public static function test ()
     +---+
         | function ()
         +---+
             | new class
             +---+
                 | public static function test ()
                 +---+
                     | function ()
                 +---+
             +---+
         +---+
     +---+
 +---+
 | function test1 ()
 | interface test2
 +---+
     | public static function test ($a, $b, $c)
 +---+
 | trait example
 +---+
     | public static function test ($a, $b, $c)
 +---+
```

Автор: [Подвирный Никита](https://vk.com/technomindlp). Специально для [Enfesto Studio Group](https://vk.com/hphp_convertation) и [Every Software](https://vk.com/evsoft)