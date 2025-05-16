<?php


namespace StackTrace\Builder\Events;


use StackTrace\Builder\BuilderContent;

class ContentUpdated
{
    public function __construct(
        public readonly BuilderContent $content
    ) { }
}
