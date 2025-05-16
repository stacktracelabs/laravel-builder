<?php


namespace StackTrace\Builder\Events;


use StackTrace\Builder\BuilderContent;

class ContentPublished
{
    public function __construct(
        public readonly BuilderContent $content
    ) { }
}
