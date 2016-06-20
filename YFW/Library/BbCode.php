<?php
namespace YFW\Library;

class BbCode
{
    public static function strip(string $message): string
    {
        // For performance
        if (strpos($message, '[') === false || strpos($message, ']') === false || strpos($message, '/') === false) {
            return $message;
        }

        return preg_replace('#\[[a-z/]+\]#si', '$1', $message);
    }

    public static function format(string $message): string
    {
        if (strpos($message, '[') === false || strpos($message, ']') === false || strpos($message, '/') === false) {
            return $message;
        }

        if (!preg_match('#\[[^\]]+\](.+?)\[/[^\]]+\]#si', $message)) {
            return $message;
        }

        $search = [
            '#\[b\](.+)\[/b\]#Uis',
            '#\[em\](.+)\[/em\]#Uis',
            '#\[u\](.+)\[/u\]#Uis',
            '#\[s\](.+)\[/s\]#Uis',
            '#\[spoiler\](.+)\[/spoiler\]#Uis',
            '#\[quote\](.+)\[/quote\]#Uis',
            '#\[code\](.+)\[/code\]#Uis',
            '#\[sup\](.+)\[/sup\]#Uis',
            '#\[sub\](.+)\[/sub\]#Uis',
            '#\[big\](.+)\[/big\]#Uis',
            '#\[small\](.+)\[/small\]#Uis',
            '#\[green\](.+)\[/green\]#Uis',
            '#\[blue\](.+)\[/blue\]#Uis',
            '#\[red\](.+)\[/red\]#Uis',
            '#\[pink\](.+)\[/pink\]#Uis',
            '#\[yellow\](.+)\[/yellow\]#Uis',
            '#\[black\](.+)\[/black\]#Uis',
            '#\[white\](.+)\[/white\]#Uis',
            '#\[brown\](.+)\[/brown\]#Uis',
            '#\[orange\](.+)\[/orange\]#Uis',
            '#\[purple\](.+)\[/purple\]#Uis',
            '#\[gray\](.+)\[/gray\]#Uis',
        ];

        $replace = [
            '<strong>$1</strong>',
            '<em>$1</em>',
            '<span class="underline">$1</span>',
            '<span class="line-through">$1</span>',
            '<span class="spoiler">$1</span>',
            '<span class="quote">$1</span>',
            '<code>$1</code>',
            '<sup>$1</sup>',
            '<sub>$1</sub>',
            '<span class="big">$1</span>',
            '<span class="small">$1</span>',
            '<span class="green">$1</span>',
            '<span class="blue">$1</span>',
            '<span class="red">$1</span>',
            '<span class="pink">$1</span>',
            '<span class="yellow">$1</span>',
            '<span class="black">$1</span>',
            '<span class="white">$1</span>',
            '<span class="brown">$1</span>',
            '<span class="orange">$1</span>',
            '<span class="purple">$1</span>',
            '<span class="gray">$1</span>',
        ];

        return preg_replace($search, $replace, $message);
    }

    public static function removeDisallowed(string $message): string
    {
        $remove = [
            '[green]',
            '[/green]',
            '[blue]',
            '[/blue]',
            '[red]',
            '[/red]',
            '[pink]',
            '[/pink]',
            '[yellow]',
            '[/yellow]',
            '[black]',
            '[/black]',
            '[white]',
            '[/white]',
            '[brown]',
            '[/brown]',
            '[orange]',
            '[/orange]',
            '[purple]',
            '[/purple]',
            '[gray]',
            '[/gray]',
            '[u]',
            '[/u]',
            '[o]',
            '[/o]',
            '[s]',
            '[/s]',
            '[quote]',
            '[/quote]',
            '[sup]',
            '[/sup]',
            '[sub]',
            '[/sub]',
            '[big]',
            '[/big]',
            '[small]',
            '[/small]',
        ];

        return str_ireplace($remove, ' ', $message);
    }
}
