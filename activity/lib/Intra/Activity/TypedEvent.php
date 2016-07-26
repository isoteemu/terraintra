<?php

/**
 * Typed event interface.
 * TypedEvent classes provides getType() function, which
 * provides type of event. Type is is used for template seeking.
 */
interface Intra_Activity_TypedEvent {
	public function getType();
}
