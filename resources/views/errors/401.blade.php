@extends('errors::minimal')

@section('title', __('Unauthorized'))
@section('code', '401')
@section('message', __('Unauthorized'))
@section('message_1', 'Tidak ada kredensial atau kredensial tidak valid')
