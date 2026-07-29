<?php

declare(strict_types=1);

namespace Company\LiveOpenSdk\Http;

interface Transport
{
    public function send(Request $request): Response;
}
