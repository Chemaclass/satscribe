<?php

declare(strict_types=1);

namespace Tests\Unit\NostrAuth;

use GMP;
use Modules\NostrAuth\Application\EventSignatureVerifier;
use PHPUnit\Framework\TestCase;

use function bin2hex;
use function hex2bin;
use function json_encode;

final class EventSignatureVerifierTest extends TestCase
{
    private const P = 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFC2F';
    private const N = 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141';
    private const GX = '79BE667EF9DCBBAC55A06295CE870B07029BFCDB2DCE28D959F2815B16F81798';
    private const GY = '483ADA7726A3C4655DA4FBFC0E1108A8FD17B448A68554199C47D08FFB10D4B8';

    public function test_invalid_signature_returns_false(): void
    {
        $verifier = new EventSignatureVerifier();

        $event = [
            'id' => str_repeat('0', 64),
            'pubkey' => str_repeat('0', 64),
            'sig' => str_repeat('0', 128),
            'created_at' => 1,
            'kind' => 22242,
            'tags' => [],
            'content' => 'x',
        ];

        $this->assertFalse($verifier->verify($event));
    }

    public function test_valid_signature_returns_true(): void
    {
        $verifier = new EventSignatureVerifier();

        $secret = gmp_init('1', 10);
        $nonce = gmp_init('2', 10);
        $pub = $this->publicKey($secret);
        $challenge = 'abc';
        $event = [
            'pubkey' => $pub,
            'created_at' => 1,
            'kind' => 22242,
            'tags' => [],
            'content' => $challenge,
        ];
        $id = hash('sha256', json_encode([0, $pub, 1, 22242, [], $challenge], JSON_UNESCAPED_SLASHES));
        $event['id'] = $id;
        $event['sig'] = $this->sign(hex2bin($id), $secret, $nonce, $pub);

        $this->assertTrue($verifier->verify($event));
    }

    private function sign(string $msg, GMP $d, GMP $k, string $pub): string
    {
        $p = gmp_init(self::P, 16);
        $n = gmp_init(self::N, 16);
        $G = ['x' => gmp_init(self::GX, 16), 'y' => gmp_init(self::GY, 16)];

        $R = $this->pointMul($k, $G, $p);
        $r = $this->mod($R['x'], $p);
        if (gmp_intval(gmp_mod($R['y'], 2)) !== 0) {
            $k = gmp_sub($n, $k);
            $R = $this->pointMul($k, $G, $p);
            $r = $this->mod($R['x'], $p);
        }

        $e = $this->challenge($r, hex2bin($pub), $msg, $n);
        $s = $this->mod(gmp_add($k, gmp_mul($e, $d)), $n);

        return str_pad(gmp_strval($r, 16), 64, '0', STR_PAD_LEFT)
            . str_pad(gmp_strval($s, 16), 64, '0', STR_PAD_LEFT);
    }

    private function publicKey(GMP $d): string
    {
        $p = gmp_init(self::P, 16);
        $G = ['x' => gmp_init(self::GX, 16), 'y' => gmp_init(self::GY, 16)];
        $P = $this->pointMul($d, $G, $p);
        return str_pad(gmp_strval($P['x'], 16), 64, '0', STR_PAD_LEFT);
    }

    private function challenge(GMP $r, string $pk, string $msg, GMP $n): GMP
    {
        $data = str_pad(gmp_strval($r, 16), 64, '0', STR_PAD_LEFT) . bin2hex($pk) . bin2hex($msg);
        $e = gmp_init(hash('sha256', hex2bin($data)), 16);
        return gmp_mod($e, $n);
    }

    private function pointMul(GMP $k, array $P, GMP $p): ?array
    {
        $Q = null;
        $N = $P;
        while (gmp_cmp($k, 0) > 0) {
            if (gmp_intval(gmp_and($k, 1)) === 1) {
                $Q = $this->pointAdd($Q, $N, $p);
            }
            $N = $this->pointAdd($N, $N, $p);
            $k = gmp_div_q($k, 2);
        }
        return $Q;
    }

    private function pointAdd(?array $p1, ?array $p2, GMP $p): ?array
    {
        if ($p1 === null) {
            return $p2;
        }
        if ($p2 === null) {
            return $p1;
        }
        if (gmp_cmp($p1['x'], $p2['x']) === 0) {
            if (gmp_cmp($p1['y'], $this->mod(gmp_neg($p2['y']), $p)) === 0) {
                return null;
            }
            $num = gmp_mul(3, gmp_powm($p1['x'], 2, $p));
            $den = gmp_mul(2, $p1['y']);
        } else {
            $num = gmp_sub($p2['y'], $p1['y']);
            $den = gmp_sub($p2['x'], $p1['x']);
        }
        $m = gmp_mul($num, gmp_invert($this->mod($den, $p), $p));
        $m = $this->mod($m, $p);
        $x3 = $this->mod(gmp_sub(gmp_sub(gmp_powm($m, 2, $p), $p1['x']), $p2['x']), $p);
        $y3 = $this->mod(gmp_sub(gmp_mul($m, gmp_sub($p1['x'], $x3)), $p1['y']), $p);
        return ['x' => $x3, 'y' => $y3];
    }

    private function mod(GMP $x, GMP $m): GMP
    {
        $res = gmp_mod($x, $m);
        if (gmp_cmp($res, 0) < 0) {
            $res = gmp_add($res, $m);
        }
        return $res;
    }
}
