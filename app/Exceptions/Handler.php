<?php

namespace App\Exceptions;

use Exception;
use App\Utils\Alarm;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        $notFound = strpos(get_class($exception), 'NotFoundHttpException') !== false;
        if ($notFound) {
            \LogUtil::info('NotFoundException =>', [
                'class' => get_class($exception),
                'path' => \Request::path(),
                'param' => \Request::all(),
                'ip' => \Request::ip(),
                'url' => \URL::full(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'msg' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ], 'exception');
        } else if (!$exception instanceof \ErrOut) {
            \LogUtil::error('Exception =>', [
                'class' => get_class($exception),
                'path' => \Request::path(),
                'param' => \Request::all(),
                'ip' => \Request::ip(),
                'url' => \URL::full(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'msg' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ], 'exception');
        }

        return parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        return parent::render($request, $exception);
    }
}
