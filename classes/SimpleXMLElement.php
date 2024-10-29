<?php

namespace block_edupublisher;

require_once __DIR__ . '/common.php';

class SimpleXMLElement extends common\SimpleXMLElement {
    static function copyElement(\SimpleXMLElement $source, \SimpleXMLElement $destination, $newName = null): void {
        if (!isset($source)) {
            throw new \Exception('Source element is not set');
        }

        // Use the provided new name or default to the source element name
        $nodeName = $newName ?? $source->getName();

        // Create a new child under the destination with the new name (or original name if newName is null)
        $newNode = $destination->addChild($nodeName);

        // Copy all attributes from the source node to the new node
        foreach ($source->attributes() ?? [] as $attrName => $attrValue) {
            $newNode->addAttribute($attrName, $attrValue);
        }

        // Check if the source node has children or not
        if ($source->children()?->count() > 0) {
            // If the source has children, recursively copy them
            foreach ($source->children() as $child) {
                static::copyElement($child, $newNode);
            }
        } else {
            // If the source has no children, it might have text content, so copy the text content
            $newNode[0] = (string)$source;
        }
    }
}
