<?php

use App\Support\Tdx\TdxClient;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

it('authenticates and returns the raw token body', function () {
    Http::fake([
        'https://tdx.test/auth' => Http::response('fake-jwt-token', 200, ['Content-Type' => 'text/plain']),
    ]);

    $client = new TdxClient('https://tdx.test', 'a-user', 'a-password');

    $token = $client->authenticate();

    expect($token)->toBe('fake-jwt-token');

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://tdx.test/auth'
            && $request['username'] === 'a-user'
            && $request['password'] === 'a-password';
    });
});

it('fetches workstations using the bearer token', function () {
    Http::fake([
        'https://tdx.test/reports/362*' => Http::response([
            'DisplayedColumns' => [
                ['HeaderText' => 'ID', 'ColumnName' => 'AssetID'],
            ],
            'DataRows' => [
                ['AssetID' => 1, 'Name' => 'IT34350'],
                ['AssetID' => 2, 'Name' => 'IT34351'],
            ],
            'ID' => 362,
        ], 200),
    ]);

    $client = new TdxClient('https://tdx.test', 'a-user', 'a-password');

    $workstations = $client->getWorkstations('fake-jwt-token');

    expect($workstations)->toHaveCount(2);

    Http::assertSent(function (Request $request) {
        return str_starts_with($request->url(), 'https://tdx.test/reports/362')
            && $request['withData'] === 'true'
            && $request->hasHeader('Authorization', 'Bearer fake-jwt-token');
    });
});

it('fetches mobile devices using the bearer token', function () {
    Http::fake([
        'https://tdx.test/reports/363*' => Http::response([
            'DisplayedColumns' => [
                ['HeaderText' => 'ID', 'ColumnName' => 'AssetID'],
            ],
            'DataRows' => [
                ['AssetID' => 1, 'Name' => 'IT98760'],
                ['AssetID' => 2, 'Name' => 'IT98761'],
            ],
            'ID' => 363,
        ], 200),
    ]);

    $client = new TdxClient('https://tdx.test', 'a-user', 'a-password');

    $mobileDevices = $client->getMobileDevices('fake-jwt-token');

    expect($mobileDevices)->toHaveCount(2);

    Http::assertSent(function (Request $request) {
        return str_starts_with($request->url(), 'https://tdx.test/reports/363')
            && $request['withData'] === 'true'
            && $request->hasHeader('Authorization', 'Bearer fake-jwt-token');
    });
});

it('fetches public wifi circuits using the bearer token', function () {
    Http::fake([
        'https://tdx.test/reports/985*' => Http::response([
            'DisplayedColumns' => [
                ['HeaderText' => 'ID', 'ColumnName' => 'AssetID'],
            ],
            'DataRows' => [
                ['AssetID' => 1, 'LocationName' => 'Wasilla Pool'],
                ['AssetID' => 2, 'LocationName' => 'Animal Care and Regulation'],
            ],
            'ID' => 985,
        ], 200),
    ]);

    $client = new TdxClient('https://tdx.test', 'a-user', 'a-password');

    $circuits = $client->getPublicWifi('fake-jwt-token');

    expect($circuits)->toHaveCount(2);

    Http::assertSent(function (Request $request) {
        return str_starts_with($request->url(), 'https://tdx.test/reports/985')
            && $request['withData'] === 'true'
            && $request->hasHeader('Authorization', 'Bearer fake-jwt-token');
    });
});

it('fetches Metronet circuits using the bearer token', function () {
    Http::fake([
        'https://tdx.test/reports/984*' => Http::response([
            'DisplayedColumns' => [
                ['HeaderText' => 'ID', 'ColumnName' => 'AssetID'],
            ],
            'DataRows' => [
                ['AssetID' => 1, 'LocationName' => 'Mat-Su Borough DSJ Building'],
                ['AssetID' => 2, 'LocationName' => 'Wasilla Library'],
            ],
            'ID' => 984,
        ], 200),
    ]);

    $client = new TdxClient('https://tdx.test', 'a-user', 'a-password');

    $circuits = $client->getMetronet('fake-jwt-token');

    expect($circuits)->toHaveCount(2);

    Http::assertSent(function (Request $request) {
        return str_starts_with($request->url(), 'https://tdx.test/reports/984')
            && $request['withData'] === 'true'
            && $request->hasHeader('Authorization', 'Bearer fake-jwt-token');
    });
});

it('throws when authentication fails', function () {
    Http::fake([
        'https://tdx.test/auth' => Http::response('Unauthorized', 401),
    ]);

    $client = new TdxClient('https://tdx.test', 'a-user', 'wrong-password');

    $client->authenticate();
})->throws(RequestException::class);
