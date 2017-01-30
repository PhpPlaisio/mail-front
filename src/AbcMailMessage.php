<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Abc\Mail;

use SetBased\Abc\Abc;
use SetBased\Abc\C;
use SetBased\Exception\LogicException;

//----------------------------------------------------------------------------------------------------------------------
/**
 * ABC's default implementation of MailMessage.
 */
class AbcMailMessage implements MailMessage
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The ID of the BLOB with the body of this message.
   *
   * @var int|null
   */
  protected $blbId;

  /**
   * The ID of the company for which this mail will be send.
   *
   * @var int
   */
  protected $cmpId;

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
   * Object constructor.
   *
   * @param int $cmpId The ID of the company.
   */
  public function __construct($cmpId)
  {
    $this->cmpId = $cmpId;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function addBcc($usrId, $address, $name = null)
  {
    $this->addHeader(C::EHD_ID_BCC, null, $usrId, $address, $name, null);

    return $this;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function addCc($usrId, $address, $name = null)
  {
    $this->addHeader(C::EHD_ID_CC, null, $usrId, $address, $name, null);

    return $this;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function addCustomHeader($name, $value)
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
  public function addFrom($usrId, $address, $name = null)
  {
    $this->addHeader(C::EHD_ID_FROM, null, $usrId, $address, $name, null);

    return $this;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function addReadReceiptTo($usrId, $address, $name = null)
  {
    $this->addHeader(C::EHD_ID_CONFIRM_READING_TO, null, $usrId, $address, $name, null);

    return $this;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function addReplyTo($usrId, $address, $name = null)
  {
    $this->addHeader(C::EHD_ID_REPLY_TO, null, $usrId, $address, $name, null);

    return $this;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function addTo($usrId, $address, $name = null)
  {
    $this->addHeader(C::EHD_ID_TO, null, $usrId, $address, $name, null);

    return $this;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function attach($blbId)
  {
    $this->addHeader(C::EHD_ID_ATTACHMENT, $blbId, null, null, null, null);

    return $this;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function embed($blbId)
  {
    // TODO: Implement embed() method.
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function send()
  {
    $count = $this->countAddressees();

    $this->validate($count);

    $transmitter = $this->getTransmitter($count);

    $elm_id = Abc::$DL->abcMailFrontInsertMessage($this->cmpId,
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
      Abc::$DL->abcMailFrontInsertMessageHeader($this->cmpId,
                                                $elm_id,
                                                $header['ehd_id'],
                                                $header['blb_id'],
                                                $header['usr_id'],
                                                $header['emh_address'],
                                                $header['emh_name'],
                                                $header['emh_value']);
    }

    foreach ($this->headers2 as $headers)
    {
      foreach ($headers as $header)
      {
        Abc::$DL->abcMailFrontInsertMessageHeader($this->cmpId,
                                                  $elm_id,
                                                  $header['ehd_id'],
                                                  $header['blb_id'],
                                                  $header['usr_id'],
                                                  $header['emh_address'],
                                                  $header['emh_name'],
                                                  $header['emh_value']);
      }
    }

    return $elm_id;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function setBody($blbId)
  {
    $this->blbId = $blbId;

    return $this;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function setMessageId($id)
  {
    $this->setHeader(C::EHD_ID_MESSAGE_ID, null, null, null, null, $id);

    return $this;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function setSender($usrId, $address, $name = null)
  {
    $this->setHeader(C::EHD_ID_SENDER, null, $usrId, $address, $name, null);

    return $this;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public function setSubject($subject)
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
  protected function addHeader($ehdId, $blbId, $usrId, $address, $name, $header)
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
                                 'emh_name'    => mb_substr($name, 0, C::LEN_EMH_NAME),
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
  protected function setHeader($ehdId, $blbId, $usrId, $address, $name, $header)
  {
    $this->validateHeader($address, $header);

    $this->headers1[$ehdId] = ['ehd_id'      => $ehdId,
                               'blb_id'      => $blbId,
                               'usr_id'      => $usrId,
                               'emh_address' => $address,
                               'emh_name'    => mb_substr($name, 0, C::LEN_EMH_NAME),
                               'emh_value'   => $header];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns an array with the number of From, To, Cc, and Bcc addresses of this message.
   *
   * @return array
   */
  private function countAddressees()
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
  private function getTransmitter($count)
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
  private function validate($count)
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
  private function validateHeader($address, $header)
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
