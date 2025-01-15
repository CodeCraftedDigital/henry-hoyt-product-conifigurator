<?php

namespace CodeCraftedDigital\ACFWooAddOns;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Interface Enqueuable
 *
 * Any class responsible for enqueuing scripts/styles should implement this.
 */
interface Enqueuable {
    public function enqueue_scripts();
}
