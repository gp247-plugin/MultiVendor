{{--
    Vendor-admin custom CSS.

    v1 carried ~400 lines here overriding AdminLTE/Bootstrap (sidebar colour
    variants, .box-body, .form-group, Bootstrap button skins…). None of that
    applies to the TailAdmin shell, so only the few rules that are not expressible
    as utility classes on the markup itself survive.
--}}
<style>
    /* Full-screen busy overlay toggled by the screens' #loading element. */
    #loading {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 50;
        background: rgba(255, 255, 255, 0.7);
    }

    .dark #loading {
        background: rgba(17, 24, 39, 0.7);
    }

    #loading .overlay {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    /* Rich text stored by the page/maintenance editors must not overflow its box. */
    #maintain_content img,
    .mvp-content img {
        max-width: 100%;
        height: auto;
    }
</style>
