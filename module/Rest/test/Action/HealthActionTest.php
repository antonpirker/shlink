<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Shlinkio\Shlink\Rest\Action\HealthAction;

class HealthActionTest extends TestCase
{
    use ProphecyTrait;

    private HealthAction $action;
    private ObjectProphecy $conn;

    public function setUp(): void
    {
        $this->conn = $this->prophesize(Connection::class);
        $em = $this->prophesize(EntityManagerInterface::class);
        $em->getConnection()->willReturn($this->conn->reveal());

        $this->action = new HealthAction($em->reveal(), new AppOptions(['version' => '1.2.3']));
    }

    /** @test */
    public function passResponseIsReturnedWhenConnectionSucceeds(): void
    {
        $ping = $this->conn->ping()->willReturn(true);

        /** @var JsonResponse $resp */
        $resp = $this->action->handle(new ServerRequest());
        $payload = $resp->getPayload();

        self::assertEquals(200, $resp->getStatusCode());
        self::assertEquals('pass', $payload['status']);
        self::assertEquals('1.2.3', $payload['version']);
        self::assertEquals([
            'about' => 'https://shlink.io',
            'project' => 'https://github.com/shlinkio/shlink',
        ], $payload['links']);
        self::assertEquals('application/health+json', $resp->getHeaderLine('Content-type'));
        $ping->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function failResponseIsReturnedWhenConnectionFails(): void
    {
        $ping = $this->conn->ping()->willReturn(false);

        /** @var JsonResponse $resp */
        $resp = $this->action->handle(new ServerRequest());
        $payload = $resp->getPayload();

        self::assertEquals(503, $resp->getStatusCode());
        self::assertEquals('fail', $payload['status']);
        self::assertEquals('1.2.3', $payload['version']);
        self::assertEquals([
            'about' => 'https://shlink.io',
            'project' => 'https://github.com/shlinkio/shlink',
        ], $payload['links']);
        self::assertEquals('application/health+json', $resp->getHeaderLine('Content-type'));
        $ping->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function failResponseIsReturnedWhenConnectionThrowsException(): void
    {
        $ping = $this->conn->ping()->willThrow(Exception::class);

        /** @var JsonResponse $resp */
        $resp = $this->action->handle(new ServerRequest());
        $payload = $resp->getPayload();

        self::assertEquals(503, $resp->getStatusCode());
        self::assertEquals('fail', $payload['status']);
        self::assertEquals('1.2.3', $payload['version']);
        self::assertEquals([
            'about' => 'https://shlink.io',
            'project' => 'https://github.com/shlinkio/shlink',
        ], $payload['links']);
        self::assertEquals('application/health+json', $resp->getHeaderLine('Content-type'));
        $ping->shouldHaveBeenCalledOnce();
    }
}
