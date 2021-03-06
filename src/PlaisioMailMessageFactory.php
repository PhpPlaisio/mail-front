<?php
declare(strict_types=1);

namespace Plaisio\Mail;

use Plaisio\PlaisioObject;

/**
 * Factory creating PlaisioMailMessage mail messages.
 */
class PlaisioMailMessageFactory extends PlaisioObject implements MailMessageFactory
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritdoc
   */
  public function create(): MailMessage
  {
    return new PlaisioMailMessage($this);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
