<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bridge\Monolog\Tests\Handler\FingersCrossed;

use Monolog\Handler\FingersCrossed\ErrorLevelActivationStrategy;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Monolog\Handler\FingersCrossed\HttpCodeActivationStrategy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HttpCodeActivationStrategyTest extends TestCase
{
    /**
     * @group legacy
     */
    public function testExclusionsWithoutCodeLegacy()
    {
        $this->expectException(\LogicException::class);
        new HttpCodeActivationStrategy(new RequestStack(), [['urls' => []]], Logger::WARNING);
    }

    /**
     * @group legacy
     */
    public function testExclusionsWithoutUrlsLegacy()
    {
        $this->expectException(\LogicException::class);
        new HttpCodeActivationStrategy(new RequestStack(), [['code' => 404]], Logger::WARNING);
    }

    /**
     * @dataProvider isActivatedProvider
     *
     * @group legacy
     */
    public function testIsActivatedLegacy($url, $record, $expected)
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create($url));

        $strategy = new HttpCodeActivationStrategy(
            $requestStack,
            [
                ['code' => 403, 'urls' => []],
                ['code' => 404, 'urls' => []],
                ['code' => 405, 'urls' => []],
                ['code' => 400, 'urls' => ['^/400/a', '^/400/b']],
            ],
            Logger::WARNING
        );

        self::assertEquals($expected, $strategy->isHandlerActivated($record));
    }

    public function testExclusionsWithoutCode()
    {
        $this->expectException(\LogicException::class);
        new HttpCodeActivationStrategy(new RequestStack(), [['urls' => []]], new ErrorLevelActivationStrategy(Logger::WARNING));
    }

    public function testExclusionsWithoutUrls()
    {
        $this->expectException(\LogicException::class);
        new HttpCodeActivationStrategy(new RequestStack(), [['code' => 404]], new ErrorLevelActivationStrategy(Logger::WARNING));
    }

    /**
     * @dataProvider isActivatedProvider
     */
    public function testIsActivated($url, $record, $expected)
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create($url));

        $strategy = new HttpCodeActivationStrategy(
            $requestStack,
            [
                ['code' => 403, 'urls' => []],
                ['code' => 404, 'urls' => []],
                ['code' => 405, 'urls' => []],
                ['code' => 400, 'urls' => ['^/400/a', '^/400/b']],
            ],
            new ErrorLevelActivationStrategy(Logger::WARNING)
        );

        self::assertEquals($expected, $strategy->isHandlerActivated($record));
    }

    public static function isActivatedProvider(): array
    {
        return [
            ['/test',  ['level' => Logger::ERROR], true],
            ['/400',   ['level' => Logger::ERROR, 'context' => self::getContextException(400)], true],
            ['/400/a', ['level' => Logger::ERROR, 'context' => self::getContextException(400)], false],
            ['/400/b', ['level' => Logger::ERROR, 'context' => self::getContextException(400)], false],
            ['/400/c', ['level' => Logger::ERROR, 'context' => self::getContextException(400)], true],
            ['/401',   ['level' => Logger::ERROR, 'context' => self::getContextException(401)], true],
            ['/403',   ['level' => Logger::ERROR, 'context' => self::getContextException(403)], false],
            ['/404',   ['level' => Logger::ERROR, 'context' => self::getContextException(404)], false],
            ['/405',   ['level' => Logger::ERROR, 'context' => self::getContextException(405)], false],
            ['/500',   ['level' => Logger::ERROR, 'context' => self::getContextException(500)], true],
        ];
    }

    private static function getContextException(int $code): array
    {
        return ['exception' => new HttpException($code)];
    }
}
