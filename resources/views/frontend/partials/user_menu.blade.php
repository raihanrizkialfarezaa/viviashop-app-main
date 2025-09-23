<aside class="user-sidebar p-3 p-md-4" style="margin-top: 5rem;">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex align-items-center mb-3">
                <div class="avatar rounded-circle bg-success text-white d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div>
                <div>
                    <div class="fw-bold text-dark">{{ auth()->user()->name }}</div>
                    <small class="text-muted">Member</small>
                </div>
            </div>
            <nav class="nav flex-column">
                <a class="nav-link text-dark py-2 px-2 rounded mb-1 {{ Request::is('profile') ? 'active' : '' }}" href="{{ url('profile') }}">Profile</a>
                <a class="nav-link text-dark py-2 px-2 rounded mb-1 {{ Request::is('orders*') ? 'active' : '' }}" href="{{ url('orders') }}">Orders</a>
                <a class="nav-link text-dark py-2 px-2 rounded mb-1 {{ Request::is('carts*') ? 'active' : '' }}" href="{{ url('carts') }}">Cart</a>
                <form action="{{ route('logout') }}" method="post" class="mt-3">
                    @csrf
                    <button class="btn btn-success w-100">Logout</button>
                </form>
            </nav>
        </div>
    </div>
</aside>
