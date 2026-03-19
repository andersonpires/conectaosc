<?php
declare(strict_types=1);

namespace FrontEnd\Controllers;

final class BiaController
{
    public function __construct(
        private readonly string $basePath
    ) {
    }

    public function index(): void
    {
        $flow = new BiaFlow($this->basePath);
        $flow->handle();
    }

    public function stream(): void
    {
        $flow = new BiaFlow($this->basePath);
        $flow->handleStream();
    }

    public function reset(): void
    {
        $flow = new BiaFlow($this->basePath);
        $flow->reset();
    }
}
