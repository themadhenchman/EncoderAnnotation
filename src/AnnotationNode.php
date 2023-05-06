<?php

namespace EncoderAnnotation;

class AnnotationNode implements \ArrayAccess
{
    /** @var AnnotationNode[] */
    private array $children = [];
    public readonly mixed $value;

    public function __construct(
        public readonly string $name,
        mixed $value = null,
        private array $attributes = [],
        public readonly ?AnnotationNode $parent = null
    )
    {
        /*
         * If $value is of the same type we are dealing with nested entity so we remove the middle
         * and use the children instead.
         */
        if (get_debug_type($value) == AnnotationNode::class) {
            $this->children = $value->getChildren();
            $this->value = null;
        } else {
            $this->value = $value;
        }
    }

    /**
     * @return AnnotationNode[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @param AnnotationNode $child
     * @return AnnotationNode
     */
    public function addChild(AnnotationNode $child): self
    {
        $this->children[$child->name] = $child;

        return $this;
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function isRoot(): bool
    {
        return is_null($this->parent);
    }

    public function isLeaf(): bool
    {
        return empty($this->children);
    }

    public function getKeys(): array
    {
        return $this->isLeaf() ? [$this->name] : array_keys($this->children);
    }

    public function getChildValue(string $key): ?AnnotationNode
    {
        return $this->children[$key]?->value;
    }

    public function offsetExists(mixed $offset): bool
    {
        if (!$this->value) {
            throw new \RuntimeException('A leaf cannot be treated as a node.');
        }

        return array_key_exists($offset, $this->value);
    }

    public function offsetGet(mixed $offset): ?AnnotationNode
    {
        if ($this->value) {
            throw new \RuntimeException('A leaf cannot be treated as a node.');
        }

        return $this->children[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        // While we could also have a leave where this is called on, the moment we treat is as an array it effectively
        // becomes a node but since we might have a value we will throw an exception in that case and fail fast.
        if ($this->value) {
            throw new \RuntimeException('A leaf cannot be treated as a node.');
        }
        $this->children[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        if ($this->value) {
            throw new \RuntimeException('A leaf cannot be treated as a node.');
        }
        unset($this->children[$offset]);
    }
}