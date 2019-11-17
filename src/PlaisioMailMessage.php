<?php
declare(strict_types=1);

namespace Plaisio\Mail;

use Plaisio\C;
use Plaisio\Kernel\Nub;
use SetBased\Exception\LogicException;

/**
 * Plaisio's default implementation of MailMessage.
 */
class PlaisioMailMessage implements MailMessage
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The ID of the BLOB with the body of this message.
   *
   * @var int|null
   */
  protected $blbId;

  /**
   * The single valued headers of this message.
   *
   * @var array[]
   */
  protected $headers1 = [];

  /**
   * The list valued headers of this message.
   *
   * @var array[]
   */
  protected $headers2 = [];

  /**
   * The subject of this message.
   *
   * @var string|null
   */
  protected $subject;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function addBcc(?int $usrId, string $address, string $name = null): MailMessage
  {
    $this->addHeader(C::EHD_ID_BCC, null, $usrId, $address, $name, null);

    return $this;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function addCc(?int $usrId, string $address, ?string $name = null): MailMessage
  {
    $this->addHeader(C::EHD_ID_CC, null, $usrId, $address, $name, null);

    return $this;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function addCustomHeader(?string $name, ?string $value): MailMessage
  {
    if ($name!==null && $value!==null)
    {
      $this->addHeader(C::EHD_ID_CUSTOM_HEADER, null, null, null, null, $name.': '.$value);
    }

    return $this;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function addFrom(?int $usrId, string $address, ?string $name = null): MailMessage
  {
    $this->addHeader(C::EHD_ID_FROM, null, $usrId, $address, $name, null);

    return $this;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function addReadReceiptTo(?int $usrId, string $address, ?string $name = null): MailMessage
  {
    $this->addHeader(C::EHD_ID_CONFIRM_READING_TO, null, $usrId, $address, $name, null);

    return $this;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function addReplyTo(?int $usrId, string $address, ?string $name = null): MailMessage
  {
    $this->addHeader(C::EHD_ID_REPLY_TO, null, $usrId, $address, $name, null);

    return $this;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function addTo(?int $usrId, string $address, ?string $name = null): MailMessage
  {
    $this->addHeader(C::EHD_ID_TO, null, $usrId, $address, $name, null);

    return $this;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function attach(int $blbId): MailMessage
  {
    $this->addHeader(C::EHD_ID_ATTACHMENT, $blbId, null, null, null, null);

    return $this;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function embed(int $blbId): string
  {
    throw new LogicException('Not implemented');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function send(): int
  {
    $cmpId = Nub::$companyResolver->getCmpId();

    $count = $this->countAddressees();
    $this->validate($count);
    $transmitter = $this->getTransmitter($count);

    $elmId = Nub::$DL->abcMailFrontInsertMessage($cmpId,
                                                 $this->blbId,
                                                 $transmitter['usr_id'],
                                                 $transmitter['emh_address'],
                                                 $transmitter['emh_name'],
                                                 $this->subject,
                                                 $count['from'],
                                                 $count['to'],
                                                 $count['cc'],
                                                 $count['bcc']);

    foreach ($this->headers1 as $header)
    {
      Nub::$DL->abcMailFrontInsertMessageHeader($cmpId,
                                                $header['blb_id'],
                                                $header['ehd_id'],
                                                $elmId,
                                                $header['usr_id'],
                                                $header['emh_address'],
                                                $header['emh_name'],
                                                $header['emh_value']);
    }

    foreach ($this->headers2 as $headers)
    {
      foreach ($headers as $header)
      {
        Nub::$DL->abcMailFrontInsertMessageHeader($cmpId,
                                                  $header['blb_id'],
                                                  $header['ehd_id'],
                                                  $elmId,
                                                  $header['usr_id'],
                                                  $header['emh_address'],
                                                  $header['emh_name'],
                                                  $header['emh_value']);
      }
    }

    return $elmId;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function setBody(int $blbId): MailMessage
  {
    $this->blbId = $blbId;

    return $this;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function setMessageId(string $id): MailMessage
  {
    $this->setHeader(C::EHD_ID_MESSAGE_ID, null, null, null, null, $id);

    return $this;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function setSender(?int $usrId, string $address, ?string $name = null): MailMessage
  {
    $this->setHeader(C::EHD_ID_SENDER, null, $usrId, $address, $name, null);

    return $this;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function setSubject(string $subject): MailMessage
  {
    $this->subject = mb_substr($subject, 0, C::LEN_ELM_SUBJECT);

    return $this;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Adds a entry to a header with a list of entries of this message.
   *
   * @param int|null    $ehdId   The ID of the attribute.
   * @param int|null    $blbId   the ID of the BLOB.
   * @param int|null    $usrId   The ID of the user.
   * @param string|null $address The address.
   * @param string|null $name    The name name associated with the address.
   * @param string|null $header  The custom header.
   *
   * @api
   * @since 1.0.0
   */
  protected function addHeader(?int $ehdId,
                               ?int $blbId,
                               ?int $usrId,
                               ?string $address,
                               ?string $name,
                               ?string $header): void
  {
    $this->validateHeader($address, $header);

    if (!isset($this->headers2[$ehdId]))
    {
      $this->headers2[$ehdId] = [];
    }

    $this->headers2[$ehdId][] = ['ehd_id'      => $ehdId,
                                 'blb_id'      => $blbId,
                                 'usr_id'      => $usrId,
                                 'emh_address' => $address,
                                 'emh_name'    => mb_substr($name ?? '', 0, C::LEN_EMH_NAME),
                                 'emh_value'   => $header];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Sets a header of this message.
   *
   * @param int|null    $ehdId   The ID of the attribute.
   * @param int|null    $blbId   The ID of the BLOB.
   * @param int|null    $usrId   The ID of the user.
   * @param string|null $address The address.
   * @param string|null $name    The name name associated with the address.
   * @param string|null $header  The custom header.
   *
   * @api
   * @since 1.0.0
   */
  protected function setHeader(?int $ehdId,
                               ?int $blbId,
                               ?int $usrId,
                               ?string $address,
                               ?string $name,
                               ?string $header): void
  {
    $this->validateHeader($address, $header);

    $this->headers1[$ehdId] = ['ehd_id'      => $ehdId,
                               'blb_id'      => $blbId,
                               'usr_id'      => $usrId,
                               'emh_address' => $address,
                               'emh_name'    => mb_substr($name ?? '', 0, C::LEN_EMH_NAME),
                               'emh_value'   => $header];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns an array with the number of From, To, Cc, and Bcc addresses of this message.
   *
   * @return array
   */
  private function countAddressees(): array
  {
    return ['from'   => (isset($this->headers2[C::EHD_ID_FROM])) ? count($this->headers2[C::EHD_ID_FROM]) : 0,
            'to'     => (isset($this->headers2[C::EHD_ID_TO])) ? count($this->headers2[C::EHD_ID_TO]) : 0,
            'cc'     => (isset($this->headers2[C::EHD_ID_CC])) ? count($this->headers2[C::EHD_ID_CC]) : 0,
            'bcc'    => (isset($this->headers2[C::EHD_ID_BCC])) ? count($this->headers2[C::EHD_ID_BCC]) : 0,
            'sender' => (isset($this->headers2[C::EHD_ID_SENDER])) ? 1 : 0];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the transmitter of this mail message.
   *
   * @param array $count The number of From, To, Cc, and Bcc addresses of this message.
   *
   * @return array
   */
  private function getTransmitter(array $count): array
  {
    if ($count['from']==1)
    {
      $ehd_id      = C::EHD_ID_FROM;
      $transmitter = $this->headers2[$ehd_id][0];

      unset($this->headers2[$ehd_id]);
    }
    else
    {
      $ehd_id      = C::EHD_ID_SENDER;
      $transmitter = $this->headers1[$ehd_id];

      unset($this->headers1[$ehd_id]);
    }

    return $transmitter;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Validates this mail messages.
   *
   * @param array $count The number of From, To, Cc, and Bcc addresses of this message.
   */
  private function validate(array $count): void
  {
    if ($count['from']>=2 && $count['sender']==0)
    {
      throw new LogicException('Sender is required when mail is send with multiple from addresses.');
    }

    if ($count['from']<=1 && $count['sender']==1)
    {
      throw new LogicException('Sender is required when and only when mail is send with multiple from addresses.');
    }

    if ($count['to']==0 && $count['cc']==0 && $count['bcc']==0)
    {
      throw new LogicException('A delivery end point is mandatory.');
    }

    if ($this->subject===null)
    {
      throw new LogicException('Subject is mandatory.');
    }

    if ($this->blbId===null)
    {
      throw new LogicException('Body is mandatory.');
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Validates the header values.
   *
   * @param string|null $address The address.
   * @param string|null $header  The custom header.
   */
  private function validateHeader(?string $address, ?string $header): void
  {
    if ($address!==null && mb_strlen($address)>C::LEN_EMH_ADDRESS)
    {
      throw new LogicException("Length of address '%s' exceeds %d characters.", $address, C::LEN_EMH_ADDRESS);
    }

    if ($header!==null && mb_strlen($header)>C::LEN_EMH_VALUE)
    {
      throw new LogicException("Length of header '%s' exceeds %d characters.", $header, C::LEN_EMH_VALUE);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
