@extends('layouts.app')
@section('title', 'Organisation Profile')
@section('page-title', 'Organisation Profile')

@section('content')
@php($logoPath = $company['company_logo_path'] ?? null)
<div class="mx-auto max-w-6xl space-y-6">
    <section class="overflow-hidden rounded-2xl bg-[#0B1E3D] px-6 py-6 text-white shadow-sm sm:px-7">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-teal-300">Organisation settings</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight">Profile and document identity</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">These details appear across procurement records, PDF documents, and official vendor communications.</p>
            </div>
            <div class="flex h-20 w-28 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-white/15 bg-white/10 p-2">
                @if($logoPath)
                    <img src="{{ asset('storage/'.$logoPath) }}" alt="Current organisation logo" class="h-full w-full object-contain">
                @else
                    <span class="text-xs font-semibold text-slate-300">No logo uploaded</span>
                @endif
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"><p class="font-semibold">Your changes were not saved. Please correct the highlighted details.</p>@foreach($errors->all() as $error)<p class="mt-1">{{ $error }}</p>@endforeach</div>
    @endif

    @include('admin.setup.fiscal-years')

    <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-5">
        @csrf @method('PUT')
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-3">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6"><h2 class="font-bold text-slate-800">Organisation details</h2><p class="mt-1 text-sm text-slate-500">The legal and contact information used on procurement documents.</p></div>
            <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                <div class="sm:col-span-2">
                    <label for="company-name" class="mb-1.5 block text-sm font-semibold text-slate-700">Organisation name <span class="text-rose-500">*</span></label>
                    <input id="company-name" required name="company_name" value="{{ old('company_name', $company['company_name'] ?? 'Kathford International College') }}" placeholder="Organisation legal name" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-100">
                </div>
                <div>
                    <label for="company-pan" class="mb-1.5 block text-sm font-semibold text-slate-700">PAN / VAT number</label>
                    <input id="company-pan" name="company_pan" value="{{ old('company_pan', $company['company_pan'] ?? '') }}" placeholder="e.g. 301936424" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-100">
                </div>
                <div>
                    <label for="company-phone" class="mb-1.5 block text-sm font-semibold text-slate-700">Phone</label>
                    <input id="company-phone" name="company_phone" value="{{ old('company_phone', $company['company_phone'] ?? '') }}" placeholder="e.g. 01-5201911" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-100">
                </div>
                <div>
                    <label for="company-email" class="mb-1.5 block text-sm font-semibold text-slate-700">Official email</label>
                    <input id="company-email" type="email" name="company_email" value="{{ old('company_email', $company['company_email'] ?? '') }}" placeholder="accounts@example.edu.np" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-100">
                </div>
                <div>
                    <label for="company-website" class="mb-1.5 block text-sm font-semibold text-slate-700">Website</label>
                    <input id="company-website" type="url" name="company_website" value="{{ old('company_website', $company['company_website'] ?? '') }}" placeholder="https://example.edu.np" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-100">
                </div>
                <div class="sm:col-span-2">
                    <label for="company-address" class="mb-1.5 block text-sm font-semibold text-slate-700">Registered address</label>
                    <textarea id="company-address" name="company_address" rows="3" placeholder="Street, municipality, district, country" class="w-full resize-y rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-100">{{ old('company_address', $company['company_address'] ?? '') }}</textarea>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6"><h2 class="font-bold text-slate-800">Logo and format</h2><p class="mt-1 text-sm text-slate-500">Your logo is resized automatically for the portal and PDF headers.</p></div>
            <div class="space-y-5 p-5 sm:p-6">
                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
                    <div class="flex items-center gap-4">
                        <div id="logo-preview-shell" class="flex h-20 w-28 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-white p-2">
                            @if($logoPath)<img id="logo-preview" src="{{ asset('storage/'.$logoPath) }}" alt="Current organisation logo" class="h-full w-full object-contain">@else<img id="logo-preview" alt="Logo preview" class="hidden h-full w-full object-contain"><span id="logo-placeholder" class="text-center text-xs text-slate-400">Logo preview</span>@endif
                        </div>
                        <div><p class="text-sm font-semibold text-slate-700">Organisation logo</p><p class="mt-1 text-xs leading-5 text-slate-500">PNG, JPG, or WEBP. Use a horizontal or square image with clear contrast. Maximum 2 MB.</p></div>
                    </div>
                    <label for="company-logo" class="mt-4 flex cursor-pointer items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-semibold text-teal-700 transition hover:bg-teal-50"><span>Choose replacement logo</span><input id="company-logo" type="file" name="company_logo" accept="image/png,image/jpeg,image/webp" class="sr-only"></label>
                    <p id="logo-file-name" class="mt-2 text-center text-xs text-slate-500">{{ $logoPath ? 'Current logo will remain unless you select a replacement.' : 'No file selected.' }}</p>
                </div>
                <div>
                    <label for="currency-symbol" class="mb-1.5 block text-sm font-semibold text-slate-700">Currency symbol</label>
                    <input id="currency-symbol" name="currency_symbol" value="{{ old('currency_symbol', $company['currency_symbol'] ?? 'Rs') }}" placeholder="Rs" maxlength="10" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-100">
                </div>

                <div>
                    <label for="date-format" class="mb-1.5 block text-sm font-semibold text-slate-700">Working date format</label>
                    <select id="date-format" name="date_format" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-100"><option value="AD" {{ old('date_format', $company['date_format'] ?? 'AD') === 'AD' ? 'selected' : '' }}>AD (Gregorian)</option><option value="BS" {{ old('date_format', $company['date_format'] ?? '') === 'BS' ? 'selected' : '' }}>BS (Bikram Sambat)</option></select>
                </div>
            </div>
        </section>

        <div class="sticky bottom-4 z-10 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur sm:flex-row sm:items-center sm:justify-between lg:col-span-5">
            <p class="text-sm text-slate-600">Changes are applied to new RFQ, Purchase Order, and Checklist PDF documents immediately.</p>
            <button type="submit" class="rounded-lg bg-teal-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-200">Save organisation profile</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('company-logo');
    const preview = document.getElementById('logo-preview');
    const placeholder = document.getElementById('logo-placeholder');
    const name = document.getElementById('logo-file-name');
    if (!input || !preview || !name) return;
    input.addEventListener('change', function () {
        const file = input.files && input.files[0];
        if (!file) return;
        preview.src = URL.createObjectURL(file);
        preview.classList.remove('hidden');
        if (placeholder) placeholder.classList.add('hidden');
        name.textContent = file.name;
    });
});
</script>
@endsection
