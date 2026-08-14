<?php

namespace App\Support\Tdx;

use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around the TDX Web API (https://support.matsu.gov/TDWebApi/api).
 *
 * TDX never gets written to from this app — see AGENTS.md. This client only
 * reads: authenticate() gets a bearer token, getWorkstations() pulls the raw
 * report rows for the hardware sync job to reconcile.
 */
final class TdxClient
{
    protected string $baseUrl;

    protected string $username;

    protected string $password;

    public function __construct(?string $baseUrl = null, ?string $username = null, ?string $password = null)
    {
        $this->baseUrl = rtrim($baseUrl ?? config('tdx.api.base_url'), '/');
        $this->username = $username ?? config('tdx.api.username') ?? '';
        $this->password = $password ?? config('tdx.api.password') ?? '';
    }

    /**
     * Authenticate against TDX and return the bearer token.
     *
     * TDX responds with the token as a raw text/plain JWT string, not JSON.
     */
    public function authenticate(): string
    {
        $response = Http::post("{$this->baseUrl}/auth", [
            'username' => $this->username,
            'password' => $this->password,
        ])->throw();

        return trim($response->body());
    }

    /**
     * Fetch the raw workstation rows from TDX report 362.
     *
     * The report response wraps rows in a DataRows array alongside column
     * metadata (DisplayedColumns, SortOrder, etc.) — this returns just the rows.
     * withData=true is required or TDX returns the report definition with
     * DataRows empty.
     *
     * @return array<int, mixed>
     */
    public function getWorkstations(string $token): array
    {
        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/reports/362", ['withData' => 'true'])
            ->throw();

        return $response->json('DataRows') ?? [];
    }

    /**
     * Fetch the raw mobile device rows from TDX report 363.
     *
     * Same shape as getWorkstations() — see that method's docblock for the
     * DataRows/withData notes.
     *
     * @return array<int, mixed>
     */
    public function getMobileDevices(string $token): array
    {
        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/reports/363", ['withData' => 'true'])
            ->throw();

        return $response->json('DataRows') ?? [];
    }
}
