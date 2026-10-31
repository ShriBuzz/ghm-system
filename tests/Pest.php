<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    // Livewire keeps static per-request state that a real request never carries over.
    ->beforeEach(fn () => Livewire::flushState())
    ->in('Feature');
