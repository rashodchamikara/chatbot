<x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
    Dashboard
</x-nav-link>

<x-nav-link :href="route('admin.leads.index')" :active="request()->routeIs('admin.leads.*')">
    Leads
</x-nav-link>

<x-nav-link :href="route('admin.conversations.index')" :active="request()->routeIs('admin.conversations.*')">
    Conversations
</x-nav-link>
<x-nav-link :href="route('admin.websites.index')" :active="request()->routeIs('admin.websites.*')">
    Websites
</x-nav-link>
<x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
    Dashboard
</x-responsive-nav-link>

<x-responsive-nav-link :href="route('admin.leads.index')" :active="request()->routeIs('admin.leads.*')">
    Leads
</x-responsive-nav-link>

<x-responsive-nav-link :href="route('admin.conversations.index')" :active="request()->routeIs('admin.conversations.*')">
    Conversations
</x-responsive-nav-link>
<x-responsive-nav-link :href="route('admin.websites.index')" :active="request()->routeIs('admin.websites.*')">
    Websites
</x-responsive-nav-link>