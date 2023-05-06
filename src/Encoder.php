<?php

namespace EncoderAnnotation;

class Encoder
{
    public function encode(AnnotationNode $node): string
    {
        return json_encode($this->buildDataForEncoding($node));
    }

    protected function buildDataForEncoding(AnnotationNode $node): array
    {
        $childResult = [];

        foreach ($node->getChildren() as $child) {
            if (is_array($child->value)) {
                $childResult[$child->name] = array_map(
                    fn ($x) => (get_debug_type($x) === AnnotationNode::class) ? $this->buildDataForEncoding($x) : $x,
                    $child->value
                );
                continue;
            }

            $childResult[$child->name] = $child->isLeaf() ? $child->value : $this->buildDataForEncoding($child);
        }

        return $childResult;
    }
}