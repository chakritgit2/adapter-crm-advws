{% extends 'layouts/loginwrap.volt' %}

{% block content %}
<div class="grid grid-cols-1 min-h-100 justify-center -mt-3 pb-16 items-center gradient-background flex-1">
    <div class="w-full max-w-md mx-auto grid grid-cols-1 mt-12 gap-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 notosan">
                {{ t('auth.sign_in') }}
            </h2>
        </div>
        <div class="bg-white p-6 mx-5 md:mx-0 rounded-lg shadow-xl">
            <form action="{{ config.loginPath }}/authenticate" method="POST" class="flex flex-col gap-y-4">

                <!-- Email Input -->
                <div>
                    <label for="email" class="block text-md font-medium text-gray-700 notosan">{{ t('auth.email') }}</label>
                    <input type="email" name="email" id="email" required tabindex="1"
                        class="mt-1 block text-black w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500">
                </div>

                <!-- Password Input -->
                <div>
                    <div class="flex justify-between items-center">
                        <label for="password" class="block text-md font-medium text-gray-700 notosan">{{ t('auth.password') }}</label>
                        <a href="https://www.mysamt.com/password-forgot?account=student" tabindex="6"
                            class="text-xs text-red-600 hover:text-red-500 notosan">{{ t('auth.forgot_password') }}</a>
                    </div>
                    <input type="password" name="password" id="password" required tabindex="2"
                        class="mt-1 block text-black w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500">
                </div>

                <!-- Remember Me -->
                <!-- <div class="flex items-center">
                    <input id="remember" name="remember" type="checkbox"
                        class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                    <label for="remember" class="ml-2 block text-sm text-gray-600">
                        Remember me
                    </label>
                </div> -->

                <!-- Submit Button -->
                <div>
                    <button type="submit" tabindex="3"
                        class="w-full notosan cursor-pointer flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        {{ t('auth.sign_in') }}
                    </button>
                </div>
            </form>

            <!-- Divider -->
            <!-- <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500 notosan">หรือ</span>
                    </div>
                </div>
            </div> -->

            <!-- Google Sign In Button -->
            <!-- <div class="mt-6">
                <a href="{{ url('login/line') }}{% if returnUrl %}?returnUrl={{ returnUrl }}{% endif %}" tabindex="4"
                    class="w-full inline-flex notosan justify-center items-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-black hover:bg-red-600 hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="26" height="26" viewBox="0 0 48 48" class="mr-2">
                        <path fill="#00c300" d="M12.5,42h23c3.59,0,6.5-2.91,6.5-6.5v-23C42,8.91,39.09,6,35.5,6h-23C8.91,6,6,8.91,6,12.5v23C6,39.09,8.91,42,12.5,42z"></path><path fill="#fff" d="M37.113,22.417c0-5.865-5.88-10.637-13.107-10.637s-13.108,4.772-13.108,10.637c0,5.258,4.663,9.662,10.962,10.495c0.427,0.092,1.008,0.282,1.155,0.646c0.132,0.331,0.086,0.85,0.042,1.185c0,0-0.153,0.925-0.187,1.122c-0.057,0.331-0.263,1.296,1.135,0.707c1.399-0.589,7.548-4.445,10.298-7.611h-0.001C36.203,26.879,37.113,24.764,37.113,22.417z M18.875,25.907h-2.604c-0.379,0-0.687-0.308-0.687-0.688V20.01c0-0.379,0.308-0.687,0.687-0.687c0.379,0,0.687,0.308,0.687,0.687v4.521h1.917c0.379,0,0.687,0.308,0.687,0.687C19.562,25.598,19.254,25.907,18.875,25.907z M21.568,25.219c0,0.379-0.308,0.688-0.687,0.688s-0.687-0.308-0.687-0.688V20.01c0-0.379,0.308-0.687,0.687-0.687s0.687,0.308,0.687,0.687V25.219z M27.838,25.219c0,0.297-0.188,0.559-0.47,0.652c-0.071,0.024-0.145,0.036-0.218,0.036c-0.215,0-0.42-0.103-0.549-0.275l-2.669-3.635v3.222c0,0.379-0.308,0.688-0.688,0.688c-0.379,0-0.688-0.308-0.688-0.688V20.01c0-0.296,0.189-0.558,0.47-0.652c0.071-0.024,0.144-0.035,0.218-0.035c0.214,0,0.42,0.103,0.549,0.275l2.67,3.635V20.01c0-0.379,0.309-0.687,0.688-0.687c0.379,0,0.687,0.308,0.687,0.687V25.219z M32.052,21.927c0.379,0,0.688,0.308,0.688,0.688c0,0.379-0.308,0.687-0.688,0.687h-1.917v1.23h1.917c0.379,0,0.688,0.308,0.688,0.687c0,0.379-0.309,0.688-0.688,0.688h-2.604c-0.378,0-0.687-0.308-0.687-0.688v-2.603c0-0.001,0-0.001,0-0.001c0,0,0-0.001,0-0.001v-2.601c0-0.001,0-0.001,0-0.002c0-0.379,0.308-0.687,0.687-0.687h2.604c0.379,0,0.688,0.308,0.688,0.687s-0.308,0.687-0.688,0.687h-1.917v1.23H32.052z"></path>
                        </svg>
                    ลงชื่อเข้าใช้ด้วย LINE
                </a>
                <a href="{{ url('login/google') }}{% if returnUrl %}?returnUrl={{ returnUrl }}{% endif %}" tabindex="4"
                    class="w-full inline-flex notosan justify-center items-center mt-2 py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-red-600 hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="26" height="26" viewBox="0 0 48 48"
                        class="mr-2">
                        <path fill="#fbc02d"
                            d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12  s5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24s8.955,20,20,20  s20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z">
                        </path>
                        <path fill="#e53935"
                            d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039  l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z">
                        </path>
                        <path fill="#4caf50"
                            d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36 c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z">
                        </path>
                        <path fill="#1565c0"
                            d="M43.611,20.083L43.595,20L42,20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571  c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z">
                        </path>
                    </svg>
                    ลงชื่อเข้าใช้ด้วย Google
                </a>
            </div> -->

            <!-- Sign Up Link -->
            <!-- <div class="mt-4 text-center text-sm">
                <span class="text-gray-600">ยังไม่มีบัญชี? </span>
                <a href="{{ url('register') }}" tabindex="5" class="font-medium text-red-600 hover:text-red-500">
                    สมัครสมาชิกฟรี
                </a>
            </div> -->
        </div>
    </div>
</div>
{% endblock %}