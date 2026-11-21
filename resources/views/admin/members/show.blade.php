@extends('layouts.admin')

@section('title', $member->user->name)
@section('header', 'Member Profile')

@section('content')
    <livewire:admin.members.member-show :member="$member" />
@endsection
