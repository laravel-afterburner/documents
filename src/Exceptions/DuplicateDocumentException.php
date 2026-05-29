<?php

namespace Afterburner\Documents\Exceptions;

use Exception;

class DuplicateDocumentException extends Exception
{
    public static function inFolder(string $name): self
    {
        return new self("Document with name '{$name}' already exists in this folder.");
    }
}
