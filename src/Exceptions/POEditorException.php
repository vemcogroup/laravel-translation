<?php

namespace Vemcogroup\Translation\Exceptions;

use Exception;

class POEditorException extends Exception
{
    public static function noApiKey(): self
    {
        return new static('Missing POEDITOR_API_KEY, please add it in .env');
    }

    public static function noProjectId(): self
    {
        return new static('Missing POEDITOR_PROJECT_ID, please add it in .env');
    }

    public static function communicationError($message): self
    {
        return new static('Error in communication with POEditor: ' . $message);
    }

    public static function unableToCreateJsDirectory($directory): self
    {
        return new static('Unable to create directory "' . $directory .'", please check permissions');
    }
}
