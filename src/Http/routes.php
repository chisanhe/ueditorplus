<?php

/**
 * UEditor Plus 路由定义
 * 
 * 定义 UEditor Plus 的后端路由
 * 
 * @package Chisanhe\Ueditorplus\Http
 * @author  chisanhe
 * @since   1.0.0
 */

use Chisanhe\Ueditorplus\Http\Controllers\UeditorController;
use Illuminate\Support\Facades\Route;

/**
 * UEditor Plus 服务路由
 * 
 * 使用 any 方法支持所有 HTTP 方法
 * 因为 UEditor 前端可能使用不同的请求方法
 */
Route::any('ueditorplus/serve', [UeditorController::class, 'serve'])
    ->name('ueditorplus.serve');
