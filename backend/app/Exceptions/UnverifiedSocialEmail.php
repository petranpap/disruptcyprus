<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * The provider did not confirm ownership of an email that already belongs to an account here,
 * so linking would let anyone with that (unverified) address take the account over.
 */
class UnverifiedSocialEmail extends RuntimeException {}
