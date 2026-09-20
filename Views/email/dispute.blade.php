{{--
    Dispute mail (S3-3) — one view per VendorNotifier event (convention guarded by
    MultiVendorNotificationsTest); $phase picks the body: opened (vendor + marketplace),
    vendor_responded (customer), resolved (customer + vendor).

    @aidlc-unit multi-vendor-pro
    @aidlc-story US-multi-vendor-pro-order-dispute
--}}
@php $phase = in_array($phase ?? '', ['opened', 'vendor_responded', 'resolved'], true) ? $phase : 'opened'; @endphp
@include('Plugins/MultiVendor::email.dispute_'.$phase)
