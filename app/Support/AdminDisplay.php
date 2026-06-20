<?php

namespace App\Support;

use App\Models\UploadedFile;
use App\Models\User;

class AdminDisplay
{
    public static function workType(?string $state): string
    {
        return [
            'traditional' => '传统创作',
            'ai' => 'AI 创作',
        ][$state] ?? '未填写';
    }

    public static function workGroup(?string $state): string
    {
        return [
            'children' => '儿童组',
            'employee' => '员工组',
        ][$state] ?? '未填写';
    }

    public static function auditStatus(?string $state): string
    {
        return [
            'draft' => '草稿',
            'submitted' => '已提交',
            'under_review' => '审核中',
            'approved' => '已通过',
            'rejected' => '已驳回',
            'published' => '已发布',
        ][$state] ?? '未填写';
    }

    public static function publishStatus(?string $state): string
    {
        return [
            'hidden' => '隐藏',
            'published' => '展示',
        ][$state] ?? '未填写';
    }

    public static function prizeStatus(?string $state): string
    {
        return [
            'active' => '启用',
            'disabled' => '禁用',
        ][$state] ?? '未填写';
    }

    public static function lotteryStatus(?string $state): string
    {
        return [
            'pending' => '待开奖',
            'won' => '已中奖',
            'lost' => '未中奖',
            'missed' => '未中奖',
        ][$state] ?? '未填写';
    }

    public static function claimStatus(?string $state): string
    {
        return [
            'submitted' => '已提交',
            'processing' => '处理中',
            'shipped' => '已寄出',
            'picked_up' => '已领取',
            'completed' => '已完成',
            'cancelled' => '已取消',
        ][$state] ?? '未填写';
    }

    public static function userStatus(?string $state): string
    {
        return [
            'active' => '启用',
            'disabled' => '禁用',
        ][$state] ?? '未填写';
    }

    public static function userRole(?string $state): string
    {
        return [
            'user' => '活动用户',
            'auditor' => '审核管理员',
            'operator' => '运营管理员',
            'admin' => '管理员',
            'super_admin' => '超级管理员',
        ][$state] ?? '未填写';
    }

    public static function fileUrl(?UploadedFile $file): ?string
    {
        return self::url($file?->url);
    }

    public static function preferredName(?User $user): string
    {
        return $user?->name ?: '未填写';
    }

    public static function url(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (is_string($path) && str_starts_with($path, '/storage/')) {
            return rtrim((string) config('app.url'), '/').$path;
        }

        if (preg_match('/^https?:\/\//i', $url) === 1) {
            return $url;
        }

        return rtrim((string) config('app.url'), '/').'/'.ltrim($url, '/');
    }

    public static function fileName(?UploadedFile $file): string
    {
        if (! $file?->url) {
            return '未上传';
        }

        return basename(parse_url($file->url, PHP_URL_PATH) ?: $file->path);
    }

    public static function isImage(?UploadedFile $file): bool
    {
        return str_starts_with((string) $file?->mime_type, 'image/');
    }

    public static function isAudio(?UploadedFile $file): bool
    {
        return str_starts_with((string) $file?->mime_type, 'audio/');
    }

    public static function isVideo(?UploadedFile $file): bool
    {
        return str_starts_with((string) $file?->mime_type, 'video/');
    }

    public static function mediaType(?UploadedFile $file): string
    {
        if (self::isImage($file)) {
            return 'image';
        }

        if (self::isAudio($file)) {
            return 'audio';
        }

        if (self::isVideo($file)) {
            return 'video';
        }

        return 'file';
    }
}
