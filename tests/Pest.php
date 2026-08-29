<?php

uses()->in(__DIR__);

function repositoryScanPayload(): array
{
    return json_decode(
        file_get_contents(__DIR__.'/fixtures/repository-scan.capability-manifest.1.json'),
        true,
    );
}
