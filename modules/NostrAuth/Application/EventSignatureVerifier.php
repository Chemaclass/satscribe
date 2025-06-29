<?php

declare(strict_types=1);

namespace Modules\NostrAuth\Application;

use GMP;

use function bin2hex;
use function hash;
use function hex2bin;
use function is_array;
use function json_encode;
use function preg_match;
use function strlen;

final class EventSignatureVerifier
{
    private const P = 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFC2F';
    private const N = 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141';
    private const GX = '79BE667EF9DCBBAC55A06295CE870B07029BFCDB2DCE28D959F2815B16F81798';
    private const GY = '483ADA7726A3C4655DA4FBFC0E1108A8FD17B448A68554199C47D08FFB10D4B8';

    public function verify(array $event): bool
    {
        if (!isset($event['id'], $event['pubkey'], $event['sig'], $event['created_at'], $event['kind'], $event['tags'], $event['content'])) {
            return false;
        }
        if (!is_array($event['tags'])) {
            return false;
        }
        if (!preg_match('/^[0-9a-f]{64}$/i', $event['pubkey'])) {
            return false;
        }
        if (!preg_match('/^[0-9a-f]{64}$/i', $event['id'])) {
            return false;
        }
        if (!preg_match('/^[0-9a-f]{128}$/i', $event['sig'])) {
            return false;
        }

        $payload = [
            0,
            strtolower($event['pubkey']),
            (int) $event['created_at'],
            (int) $event['kind'],
            $event['tags'],
            (string) $event['content'],
        ];

        $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            return false;
        }
        $id = hash('sha256', $encoded);
        if (strtolower($event['id']) !== $id) {
            return false;
        }

        $msg = hex2bin($id);
        $sig = hex2bin($event['sig']);
        $pk = hex2bin($event['pubkey']);
        if ($msg === false || $sig === false || $pk === false || strlen($sig) !== 64 || strlen($pk) !== 32) {
            return false;
        }

        return $this->verifySchnorr($msg, $sig, $pk);
    }

    private function verifySchnorr(string $msg, string $sig, string $pk): bool
    {
        $p = gmp_init(self::P, 16);
        $n = gmp_init(self::N, 16);

        $r = gmp_init(bin2hex(substr($sig, 0, 32)), 16);
        $s = gmp_init(bin2hex(substr($sig, 32, 32)), 16);

        if (gmp_cmp($r, 0) < 0 || gmp_cmp($r, $p) >= 0) {
            return false;
        }
        if (gmp_cmp($s, 0) < 0 || gmp_cmp($s, $n) >= 0) {
            return false;
        }

        $Px = gmp_init(bin2hex($pk), 16);
        $P = $this->liftX($Px, $p);
        if ($P === null) {
            return false;
        }

        $e = $this->challenge($r, $pk, $msg, $n);
        $G = ['x' => gmp_init(self::GX, 16), 'y' => gmp_init(self::GY, 16)];
        $sG = $this->pointMul($s, $G, $p);
        $eP = $this->pointMul($e, $P, $p);
        $R = $this->pointAdd($sG, $this->pointNeg($eP, $p), $p);
        if ($R === null) {
            return false;
        }
        if (gmp_cmp($R['x'], $r) !== 0) {
            return false;
        }
        return gmp_intval(gmp_mod($R['y'], 2)) === 0;
    }

    private function challenge(GMP $r, string $pk, string $msg, GMP $n): GMP
    {
        $data = str_pad(gmp_strval($r, 16), 64, '0', STR_PAD_LEFT) . bin2hex($pk) . bin2hex($msg);
        $e = gmp_init(hash('sha256', hex2bin($data)), 16);
        return gmp_mod($e, $n);
    }

    private function liftX(GMP $x, GMP $p): ?array
    {
        if (gmp_cmp($x, $p) >= 0) {
            return null;
        }

        $y2 = $this->mod(gmp_add(gmp_powm($x, 3, $p), 7), $p);
        $y = $this->sqrt($y2, $p);
        if (!$y instanceof GMP) {
            return null;
        }
        if (gmp_intval(gmp_mod($y, 2)) !== 0) {
            $y = $this->mod(gmp_neg($y), $p);
        }

        return ['x' => $x, 'y' => $y];
    }

    private function sqrt(GMP $a, GMP $p): ?GMP
    {
        $exp = gmp_div_q(gmp_add($p, 1), 4);
        $y = gmp_powm($a, $exp, $p);
        if (gmp_cmp($this->mod(gmp_powm($y, 2, $p), $p), $this->mod($a, $p)) !== 0) {
            return null;
        }
        return $y;
    }

    private function pointNeg(array $P, GMP $p): array
    {
        return ['x' => $P['x'], 'y' => $this->mod(gmp_neg($P['y']), $p)];
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

    private function mod(GMP $x, GMP $m): GMP
    {
        $res = gmp_mod($x, $m);
        if (gmp_cmp($res, 0) < 0) {
            $res = gmp_add($res, $m);
        }
        return $res;
    }
}
