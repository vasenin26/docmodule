<?php

namespace App\Services;

use App\Models\Agent;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Config;

class AgentJwtService
{
    private string $secretKey;
    private int $expirationTime;
    
    public function __construct()
    {
        $this->secretKey = Config::get('app.key');
        $this->expirationTime = 365 * 24 * 60 * 60; // 1 год в секундах
    }
    
    /**
     * Генерирует JWT токен для агента
     */
    public function generateToken(Agent $agent): string
    {
        $payload = [
            'agent_id' => $agent->id,
            'project_id' => $agent->project_id,
            'iat' => time(),
            'exp' => time() + $this->expirationTime,
        ];
        
        return JWT::encode($payload, $this->secretKey, 'HS256');
    }
    
    /**
     * Валидирует JWT токен и возвращает агента
     */
    public function validateToken(string $token): ?Agent
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            
            // Проверяем, что токен не истек
            if ($decoded->exp < time()) {
                return null;
            }
            
            // Ищем агента в базе данных
            $agent = Agent::where('id', $decoded->agent_id)
                         ->where('project_id', $decoded->project_id)
                         ->where('token', $token)
                         ->first();
            
            return $agent;
            
        } catch (\Exception $e) {
            // Логируем ошибку для отладки
            \Log::warning('JWT validation failed', [
                'error' => $e->getMessage(),
                'token' => substr($token, 0, 20) . '...'
            ]);
            
            return null;
        }
    }
    
    /**
     * Аннулирует токен агента (генерирует новый)
     */
    public function revokeToken(Agent $agent): string
    {
        $newToken = $this->generateToken($agent);
        $agent->update(['token' => $newToken]);
        
        return $newToken;
    }
    
    /**
     * Проверяет, валиден ли токен без поиска в БД
     */
    public function isTokenValid(string $token): bool
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            return $decoded->exp >= time();
        } catch (\Exception $e) {
            return false;
        }
    }
}
