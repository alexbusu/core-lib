<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

/**
 * Class DateTimeHelper
 */
class DateTimeHelper
{

    /**
     * @var string
     */
    private $string;

    /**
     * @var string
     */
    private $format;

    /**
     * @var string
     */
    private $timezone;

    /**
     * @var \DateTimeZone
     */
    private $utc;

    /**
     * @var \DateTimeZone
     */
    private $local;

    /**
     * @var \DateTime
     */
    private $datetime;

    /**
     * @param string $string     Datetime string
     * @param string $fromFormat Format the string is in
     * @param string $timezone   Timezone the string is in
     */
    public function __construct($string = '', $fromFormat = 'Y-m-d H:i:s', $timezone = 'UTC')
    {
        $this->setDateTime($string, $fromFormat, $timezone);
    }

    /**
     * Sets date/time
     *
     * @param \DateTime|string $datetime
     * @param string           $fromFormat
     * @param string           $timezone
     */
    public function setDateTime($datetime = '', $fromFormat = 'Y-m-d H:i:s', $timezone = 'local')
    {
        if ($timezone == 'local') {
            $timezone = date_default_timezone_get();
        } elseif (empty($timezone)) {
            $timezone = 'UTC';
        }

        $this->format   = (empty($fromFormat)) ? 'Y-m-d H:i:s' : $fromFormat;
        $this->timezone = $timezone;

        $this->utc   = new \DateTimeZone('UTC');
        $this->local = new \DateTimeZone(date_default_timezone_get());

        if ($datetime instanceof \DateTime) {
            $this->datetime = $datetime;
            $this->string = $this->datetime->format($fromFormat);
        } elseif (empty($datetime)) {
            $this->datetime = new \DateTime("now", new \DateTimeZone($this->timezone));
            $this->string = $this->datetime->format($fromFormat);
        } else {
            $this->string = $datetime;

            $this->datetime = \DateTime::createFromFormat(
                $this->format,
                $this->string,
                new \DateTimeZone($this->timezone)
            );

            if ($this->datetime === false) {
                //the format does not match the string so let's attempt to fix that
                $this->string = date($this->format, strtotime($datetime));
                $this->datetime = \DateTime::createFromFormat(
                    $this->format,
                    $this->string
                );
            }
        }
    }

    /**
     * @param string $format
     *
     * @return string
     */
    public function toUtcString($format = null)
    {
        if ($this->datetime) {
            $utc = $this->datetime->setTimezone($this->utc);
            if (empty($format)) {
                $format = $this->format;
            }
            return $utc->format($format);
        }

        return $this->string;
    }

    /**
     * @param string $format
     *
     * @return string
     */
    public function toLocalString($format = null)
    {
        if ($this->datetime) {
            $local = $this->datetime->setTimezone($this->local);
            if (empty($format)) {
                $format = $this->format;
            }

            return $local->format($format);
        }

        return $this->string;
    }

    /**
     * @return \DateTime
     */
    public function getUtcDateTime()
    {
        return $this->datetime->setTimezone($this->utc);
    }

    /**
     * @return \DateTime
     */
    public function getLocalDateTime()
    {
        return $this->datetime->setTimezone($this->local);
    }

    /**
     * @param null $format
     *
     * @return string
     */
    public function getString($format = null)
    {
        if (empty($format)) {
            $format = $this->format;
        }

        return $this->datetime->format($format);
    }

    /**
     * @return \DateTime
     */
    public function getDateTime()
    {
        return $this->datetime;
    }

    /**
     * @return bool|int
     */
    public function getLocalTimestamp()
    {
        if ($this->datetime) {
            $local = $this->datetime->setTimezone($this->local);
            return $local->getTimestamp();
        }

        return false;
    }

    /**
     * @return bool|int
     */
    public function getUtcTimestamp()
    {
        if ($this->datetime) {
            $utc = $this->datetime->setTimezone($this->utc);
            return $utc->getTimestamp();
        }

        return false;
    }
}
