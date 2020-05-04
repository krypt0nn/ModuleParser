<?php

/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * @package     ModuleParser
 * @copyright   2020 Podvirnyy Nikita (Observer KRypt0n_)
 * @license     GNU GPLv3 <https://www.gnu.org/licenses/gpl-3.0.html>
 * @author      Podvirnyy Nikita (Observer KRypt0n_)
 * 
 * Contacts:
 *
 * Email: <suimin.tu.mu.ga.mi@gmail.com>
 * VK:    <https://vk.com/technomindlp>
 *        <https://vk.com/hphp_convertation>
 * 
 */

namespace ModuleParser;

/**
 * Объект представления информации о структуре класса/функции и т.п.
 * 
 * @var string $type - тип структуры
 * 
 * Принимает значения:
 * * function           - функция           function example (...) {...}
 * * lambda_function    - лямбда-функция    $func = function (...) {...}
 * * class              - класс             class example {...}
 * * lambda_class       - лямбда-класс      new class {...}
 * * interface          - интерфейс         interface example {...}
 * * trait              - трейт             trait example {...}
 * 
 * @var string $name        - название структуры (имя класса, функции, трейта и т.п.)
 * @var string $description - описание структуры (полный текст имени, к примеру - public static function test ($a, $b))
 * @var int $begin          - индекс начала структуры (начиная с первого ключевого слова будь то public/function или т.п.)
 * @var int $end            - индекс конца структуры (закрывающая фигурная скобка или точка с запятой в абстрактных функциях)
 * 
 * [@var array $subitems = array ()] - вложенные в структуру элементы (для классов - функции, для функций - анонимные функции и т.п.)
 * [@var int $points = 0]            - системная переменная. Не используется на практике
 */
final class Item
{
    public $type;
    public $name;
    public $description;
    public $begin;
    public $end;
    public $subitems = array ();

    public $points = 0;
}

/**
 * Парсер модулей PHP класса
 */
class ModuleParser
{
    public static $parse_lambda_functions = true; // Парсить ли анонимные функции
    public static $parse_lambda_classes   = true; // Парсить ли анонимные классы

    # Список символов, после которых разрешено использование конструкций class, function, interface или trait
    # whitespace (пробельные символы) проверяются отдельно, их указывать не надо
    public static $allowed_chars = array (
        '(', '[', '{',
        ')', ']', '}',
        '=', '&', '@'
    );

    # Список ключевых слов для функций и классов
    public static $keywords = array (
        'public', 'static', 'final', 'abstract'
    );

    /**
     * Парсинг модулей
     * 
     * @param string $code - PHP код или путь до файла с PHP кодом для парсинга
     * 
     * @return array - возвращает массив Item'ов
     */
    public static function parse ($code)
    {
        # Читаем содержимое файла если передан путь на него
        if (file_exists ($code))
            $code = file_get_contents ($code);
        
        $length = strlen ($code);

        # Символы строк
        $codebreak  = null;
        $codebreaks = array ('\'', '"');

        # Стек ключевых слов (public, static и т.п.)
        $keywords_stack = array ();
        $keywords_begin = 0;

        /**
         * Стек Item'ов
         * Он необходим для правильной обработки структур вида
         * 
         * class example
         * {
         *     public function a () {}
         *     public function b () {}
         *     public function c () {}
         * }
         * 
         * example
         * example -> a
         * example -> b
         * example -> c
         */
        $items = array ();
        $items_stack = array ();
        $item = false;

        # Проходим по всем символам кода
        for ($i = 0; $i < $length; ++$i)
        {
            # Если текущий символ является строковым (' или ") - открываем или закрываем строку
            if (in_array ($code[$i], $codebreaks) && (($codebreak !== null && $codebreak == $code[$i]) || $codebreak === null))
            {
                $shield = false;

                for ($j = $i - 1; $code[$j] == '\\'; --$j)
                    $shield = !$shield;

                # Исключаем экранированные символы
                if (!$shield)
                    $codebreak = $codebreak === null ? $code[$i] : null;
            }

            # Если мы не находимся внутри строки
            if ($codebreak == null)
            {
                # Пропускаем комментарии (//, # и /* */)
                if ($i < $length - 1 && ($code[$i] == '/' || $code[$i] == '#'))
                    $i = strpos ($code, $code[$i + 1] == '*' ? '*/' : "\n", $i + 1);

                # Пропускаем heredoc и nowdoc (https://www.php.net/manual/ru/language.types.string.php)
                if ($i < $length - 2 && substr ($code, $i, 3) == '<<<')
                {
                    $i += 3;

                    $token = trim (trim (substr ($code, $i, strpos ($code, "\n", $i) - $i)), '\'');
                    
                    $i = strpos ($code, "\n$token;", $i);
                }

                # Если найден { - увеличиваем счётчик points текущего Item на 1
                if ($code[$i] == '{' && $item !== false)
                    ++$item->points;

                # Если найден } - уменьшаем счётчик points текущего Item на 1
                # Всё это нужно для обработки правильной скобочной последовательности, чтобы корректно найти начало и конец структуры
                elseif ($code[$i] == '}' && $item !== false)
                {
                    --$item->points;

                    # Если счётчик на нуле (скобочная последовательность закончилась) и при этом мы уже обнаружили структуру
                    # - сохраняем её для вывода
                    if ($item->points == 0 && $item->name)
                    {
                        $item->end = $i;

                        # А если мы ранее находили структуры, которые по началу и концу входят в текущую - добавляем их в subitems текущей структуры
                        foreach ($items as $id => $nitem)
                            if ($nitem->begin > $item->begin && $nitem->end < $item->end)
                            {
                                $item->subitems[] = $nitem;

                                unset ($items[$id]);
                            }

                        // echo ' (*) '. $item->type .' '. $item->name .' : '. implode (', ', array_map (fn ($t) => $t->name, $items_stack)) . PHP_EOL;

                        # Очищаем стек Item'ов на 1 элемент
                        $items[] = $item;
                        array_pop ($items_stack);

                        $item = end ($items_stack);
                    }
                }

                # Если текущий символ точно не фигурная скобка и при этом предыдущий символ является разрешённым (whitespace / self::$allowed_chars)
                elseif ($i > 0 && (trim ($code[$i - 1]) == '' || in_array ($code[$i - 1], self::$allowed_chars)))
                {
                    # Обработка функций и лямбда-функций
                    if (strtolower (substr ($code, $i, 8)) == 'function' && (trim ($code[$i + 8]) == '' || in_array ($code[$i + 8], self::$allowed_chars)))
                    {
                        # Парсим имя и аргументы функции
                        $name = trim (substr ($code, $i += 8, ($args_begin = strpos ($code, '(', $i)) - $i));
                        $args = str_replace (array ("\n", "\r"), '', substr ($code, $args_begin, ($args_end = strpos ($code, ')', $args_begin)) - $args_begin + 1));

                        # Если у функции есть имя - она не анонимная
                        if ($name)
                        {
                            # Добавляем в стек Item'ов новый элемент и записываем в него информацию
                            $items_stack[] = new Item;
                            $item = end ($items_stack);
                            
                            $item->type  = 'function';
                            $item->begin = $i;
                            $item->name  = $name;
                            $item->description = trim (implode (' ', $keywords_stack) .' function '. $name .' '. $args);

                            /**
                             * Если после аргументов функции идёт точка с запятой - очевидно, что алгоритм
                             * на правильной скобочной последовательности ({ и } выше) не сможет
                             * обнаружить ситуацию когда $points = 0 и правильно обработать такую функцию
                             * поэтому мы ручками проверяем её "на вшивость", лол
                             * 
                             * К примеру:
                             * 
                             * interface example
                             * {
                             *     public function some_abstract_function ();
                             * }
                             */
                            if (trim (substr ($code, $args_end += 1, ($func_end = strpos ($code, ';', $args_end)) - $args_end)) == '')
                            {
                                $item->end = $func_end;

                                $items[] = $item;
                                array_pop ($items_stack);

                                $item = end ($items_stack);
                            }
                        }

                        # Если имени нет - это анонимная функция
                        # function () {}
                        elseif (self::$parse_lambda_functions)
                        {
                            $items_stack[] = new Item;
                            $item = end ($items_stack);
                            
                            $item->type  = 'lambda_function';
                            $item->begin = $i;
                            $item->name  = '<lambda function @ '. $item->begin .'>'; // В качестве имени я использую конструкцию <lambda ... @ начало структуры>
                            $item->description = 'function '. $args;
                        }

                        // echo ' [@] function '. $item->name . PHP_EOL;

                        # Если у структуры есть ключевые слова - смещаем её начало к началу ключевых слов
                        if (sizeof ($keywords_stack) > 0)
                            $item->begin = $keywords_begin;

                        # Очищаем стек ключевых слов чтобы не смешивать их с разными структурами
                        $keywords_stack = array ();
                    }

                    # Обработка классов и лямбда-классов
                    elseif (strtolower (substr ($code, $i, 5)) == 'class' && (trim ($code[$i + 5]) == '' || in_array ($code[$i + 5], self::$allowed_chars)))
                    {
                        # Парсим имя класса
                        $name = trim (substr ($code, $i += 5, strpos ($code, '{', $i) - $i));
                        
                        # Если у класса есть имя - он не анонимный
                        if ($name)
                        {
                            # Добавляем новый Item в стек и записываем в него информацию о классе
                            $items_stack[] = new Item;
                            $item = end ($items_stack);

                            $item->type  = 'class';
                            $item->begin = $i;
                            $item->description = trim (implode (' ', $keywords_stack) .' class '. $name);
                            
                            /**
                             * Обрезаем из названия класса extends и implements
                             * 
                             * К примеру:
                             * 
                             * class example extends alala {...}
                             */
                            $name = preg_split ('/[\s]/', $name);

                            $item->name = $name[0];
                        }
                        
                        # Если имени нет - это анонимный класс
                        # new class {}
                        elseif (self::$parse_lambda_classes)
                        {
                            $items_stack[] = new Item;
                            $item = end ($items_stack);

                            $item->type  = 'lambda_class';
                            $item->begin = $i;
                            $item->name  = '<lambda class @ '. $item->begin .'>';
                            $item->description = 'new class';
                        }

                        // echo ' [@] class '. $item->name . PHP_EOL;

                        # Если у структуры есть ключевые слова - смещаем её начало к началу ключевых слов
                        if (sizeof ($keywords_stack) > 0)
                            $item->begin = $keywords_begin;

                        # Очищаем стек ключевых слов чтобы не смешивать их с разными структурами
                        $keywords_stack = array ();
                    }

                    # Обработка интерфейсов
                    elseif (strtolower (substr ($code, $i, 9)) == 'interface' && (trim ($code[$i + 9]) == '' || in_array ($code[$i + 9], self::$allowed_chars)))
                    {
                        # Добавляем интерфейс в стек Item'ов
                        $items_stack[] = new Item;
                        $item = end ($items_stack);

                        $item->type  = 'interface';
                        $item->begin = $i;
                        $item->name  = trim (substr ($code, $i += 9, strpos ($code, '{', $i) - $i));
                        $item->description = 'interface '. $item->name;

                        // echo ' [@] interface '. $item->name . PHP_EOL;
                    }

                    # Обработка трейтов
                    elseif (strtolower (substr ($code, $i, 5)) == 'trait' && (trim ($code[$i + 5]) == '' || in_array ($code[$i + 5], self::$allowed_chars)))
                    {
                        # Добавляем трейт в стек Item'ов
                        $items_stack[] = new Item;
                        $item = end ($items_stack);

                        $item->type  = 'trait';
                        $item->begin = $i;
                        $item->name  = trim (substr ($code, $i += 5, strpos ($code, '{', $i) - $i));
                        $item->description = 'trait '. $item->name;

                        // echo ' [@] trait '. $item->name . PHP_EOL;
                    }

                    # Если это ни интерфейс, ни трейт, ни класс и не функция - проверим, может это ключевое слово?
                    else
                    {
                        $founded = false;

                        # Проходимся по списку ключевых слов
                        foreach (self::$keywords as $keyword)
                            if (strtolower (substr ($code, $i, strlen ($keyword))) == $keyword) // Если такое найдено
                            {
                                # Добавляем ключевое слово в стек и говорим что оно найдено
                                $keywords_stack[] = $keyword;
                                $founded = true;

                                break;
                            }

                        # Если это первое ключевое слово - значит это начало описания для какой-нибудь будущей структуры. Сохраняем
                        if (sizeof ($keywords_stack) == 1)
                            $keywords_begin = $i;

                        /**
                         * Если это не ключевое слово - значит очищаем стек
                         * 
                         * public static nothing function
                         * 
                         * Так ведь не может быть, верно? А вообще, это сделано чтобы очистить стек если в него случайно попадёт
                         * какое-то ключевое слово, которое просто случайно встретилось в коде
                         * и не относится ни к одной структуре
                         */
                        if (!$founded)
                            $keywords_stack = array ();
                    }
                }
            }
        }

        # Возвращаем список структур
        return array_values ($items);
    }
}
