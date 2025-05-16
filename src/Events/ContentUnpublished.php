<?php


namespace StackTrace\Builder\Events;


use StackTrace\Builder\BuilderContent;

class ContentUnpublished
{
    public function __construct(
        public readonly BuilderContent $content
    ) { }
}
