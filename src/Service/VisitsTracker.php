<?php
namespace Acelaya\UrlShortener\Service;

use Acelaya\UrlShortener\Entity\ShortUrl;
use Acelaya\UrlShortener\Entity\Visit;
use Acelaya\UrlShortener\Exception\InvalidArgumentException;
use Acelaya\UrlShortener\Exception\InvalidShortCodeException;
use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Doctrine\ORM\EntityManagerInterface;
use Zend\Paginator\Paginator;

class VisitsTracker implements VisitsTrackerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * VisitsTracker constructor.
     * @param EntityManagerInterface $em
     *
     * @Inject({"em"})
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Tracks a new visit to provided short code, using an array of data to look up information
     *
     * @param string $shortCode
     * @param array $visitorData Defaults to global $_SERVER
     */
    public function track($shortCode, array $visitorData = null)
    {
        $visitorData = $visitorData ?: $_SERVER;

        /** @var ShortUrl $shortUrl */
        $shortUrl = $this->em->getRepository(ShortUrl::class)->findOneBy([
            'shortCode' => $shortCode,
        ]);

        $visit = new Visit();
        $visit->setShortUrl($shortUrl)
              ->setUserAgent($this->getArrayValue($visitorData, 'HTTP_USER_AGENT'))
              ->setReferer($this->getArrayValue($visitorData, 'HTTP_REFERER'))
              ->setRemoteAddr($this->getArrayValue($visitorData, 'REMOTE_ADDR'));
        $this->em->persist($visit);
        $this->em->flush();
    }

    /**
     * @param array $array
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    protected function getArrayValue(array $array, $key, $default = null)
    {
        return isset($array[$key]) ? $array[$key] : $default;
    }

    /**
     * Returns the visits on certain shortcode
     *
     * @param $shortCode
     * @return Paginator|Visit[]
     */
    public function info($shortCode)
    {
        /** @var ShortUrl $shortUrl */
        $shortUrl = $this->em->getRepository(ShortUrl::class)->findOneBy([
            'shortCode' => $shortCode,
        ]);
        if (! isset($shortUrl)) {
            throw new InvalidArgumentException(sprintf('Short code "%s" not found', $shortCode));
        }

        return $this->em->getRepository(Visit::class)->findBy([
            'shortUrl' => $shortUrl,
        ], [
            'date' => 'DESC'
        ]);
    }
}