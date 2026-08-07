<svg
    viewBox="0 0 64 64"
    xmlns="http://www.w3.org/2000/svg"
    fill="none"
    {{ $attributes }}
>
    <defs>
        <linearGradient
            id="omnimerge-logo-gradient"
            x1="10"
            y1="8"
            x2="54"
            y2="56"
            gradientUnits="userSpaceOnUse"
        >
            <stop stop-color="#818CF8"/>
            <stop offset="0.5" stop-color="#8B5CF6"/>
            <stop offset="1" stop-color="#EC4899"/>
        </linearGradient>
    </defs>

    <!-- Marco -->
    <rect
        x="5"
        y="5"
        width="54"
        height="54"
        rx="17"
        fill="url(#omnimerge-logo-gradient)"
    />

    <!-- Órbitas -->
    <ellipse
        cx="32"
        cy="32"
        rx="19"
        ry="8"
        stroke="white"
        stroke-width="2.6"
        opacity="0.9"
    />

    <ellipse
        cx="32"
        cy="32"
        rx="8"
        ry="19"
        stroke="white"
        stroke-width="2.6"
        opacity="0.9"
        transform="rotate(42 32 32)"
    />

    <!-- Centro -->
    <circle
        cx="32"
        cy="32"
        r="5"
        fill="white"
    />

    <!-- Nodos -->
    <circle
        cx="14.5"
        cy="31.5"
        r="2.7"
        fill="white"
    />

    <circle
        cx="46"
        cy="21"
        r="2.7"
        fill="white"
    />

    <circle
        cx="41"
        cy="47"
        r="2.7"
        fill="white"
    />
</svg>