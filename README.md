<h1 align="center">🚀 ModuleParser</h1>

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

<p align="center">или</p>

Скачайте файл `ModuleParser.php` и подключите его к проекту с помощью *require*

```php
<?php

require 'ModuleParser.php';
```

## Быстрый старт

Для получения информации о синтаксических конструкциях внутри кода необходимо вызвать метод `ModuleParser\ModuleParser::parse(string $code): array`

Данный метод принимает в качестве единственного аргумента либо текст, из которого необходимо получить все синтаксические структуры (классы, функции, интерфейсы и трейты), либо путь до файла, из которого этот текст необходимо взять

В качестве результата метод возвращает массив объектов `ModuleParser\Item`, которые представляют собой структуры хранения информации о синтаксической структуре. Вот список доступных свойств

| Свойство | Тип | Описание |
| - | - | - |
| type | string | Тип синтаксической структуры. Доступные значения: function, lambda_function, class, lambda_class, interface, trait
| name | string | Название структуры. Имя функции / класса либо специальное значение вида <type @ random int>. Не является уникальным идентификатором
| description | string | Полное описание структуры формата `public function example($a): int`
| begin | int | Индекс начала структуры внутри кода. Под началом подразумевается первый символ ключевых слов структуры (public, class и т.п.) |
| bracket_begin | int \| null | Индекс начала структуры внутри кода, где под началом подразумевается первый символ открывающейся фигурной скобки. Может быть равен null если этого символа нет (к примеру, у функций интерфейсов) |
| end | int | Индекс конца структуры внутри кода. Может указывать как на закрывающую фигурную скобку, так и на точку с запятой (см. выше) |
| code | string \| null | Код синтаксической структуры начиная с bracket_begin и заканчивая end. Может быть равен пустой строке либо null, если код не указан (см. выше) |
| definition | string | Код объявления синтаксической структуры начиная с begin и заканчивая end. Как и код выше, автоматически приводится в нижнее значение табуляции |
| return_type | string \| null | Тип возвращаемого значения (для функций). Если не указан - принимает значение null |
| subitems | array | Массив дочерних элементов, аналогичный возвращаемому методом `ModuleParser\ModuleParser::parse`. Для классов - это функции, которые находятся внутри него, и т.п. |
| keywords | array | Массив ключевых слов структуры. К примеру, `[public, static]` |
| arguments | array | Массив аргументов функций. К примеру, `[$a, $b = null]` |
| points = 0 | int | Системное свойство, отвечающее в процессе работы за анализ правильной скобочной последовательности. **Не используется на практике** |

Помимо этого класс `ModuleParser\ModuleParser` имеет несколько статичных свойств

| Свойство | Тип | Описание |
| - | - | - |
| parse_lambda_functions = true | bool | Парсить ли анонимные функции |
| parse_lambda_classes = true | bool | Парсить ли анонимные классы |
| allowed_chars = [...] | array | Список символов, после которых разрешено использование синтаксических конструкций |
| keywords = [...] | array | Список ключевых слов для функций и классов |
| tab_weight = 4 | int | Количество пробелов, соразмерных с одним табом |

К примеру, для оптимизации работы кода вы можете отключить парсинг лямбда-классов и функций

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
     | function __construct($onwer = nil, $init = true, $self = nil)
     | public function __initComponentInfo()
     +---+
         | function() use ($obj)
         | function() use ($obj)
         | function() use ($obj)
         | function() use ($obj)
     +---+
 +---+
 | function ()
 | new class
 +---+
     | public static function test()
     +---+
         | function ()
         +---+
             | new class
             +---+
                 | public static function test()
                 +---+
                     | function()
                 +---+
             +---+
         +---+
     +---+
 +---+
 | function test1()
 | interface test2
 +---+
     | public static function test($a, $b, $c)
 +---+
 | trait example
 +---+
     | public static function test($a, $b, $c)
 +---+
```

## Известные проблемы
- [ ] Возможно использование многострочных комментариев для сбивания алгоритма

```php
<?php

public /* 1 */ function /* 2 */ example /* 3 */ (/* 4 */)
{
	// do something
}

```

Автор: [Подвирный Никита](https://vk.com/technomindlp). Специально для [Enfesto Studio Group](https://vk.com/hphp_convertation) и [Every Software](https://vk.com/evsoft)
