<?php


namespace StackTrace\Builder;


use Illuminate\Contracts\Support\Arrayable;

class BuilderModel implements Arrayable
{
    public function __construct(
        public readonly string $id,
        public readonly string $name
    ) { }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }

    public static function fromArray(array $model): static
    {
        return new static($model['id'], $model['name']);
    }
}
