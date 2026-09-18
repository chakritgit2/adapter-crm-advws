{% extends "layouts/main.volt" %}

{% block content %}

    <div style="display: flex;flex-direction: column;align-items: center; padding-top:5rem; padding-bottom:10rem;">
        <div style="overflow:hidden;border-radius:10em;background:rgb(191 0 36);width:224px">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQBBBgKcd8rqn-Os5obtc552CljajTQOVbUuA&s"
                alt="SAMT Music Logo">
        </div>
        <div>
            <div style="margin-top:1rem;text-align:center">
                <div class="notosans" style="font-size: 5rem;color:white;margin:0">404</div>
                <div class="notosan" style="font-size: 2rem;color:white">{{ t('error.404_message') }}</div>
                <div class="notosan"><a href="https://www.mysamt.com" style="color:white">{{ t('error.back_home') }}</a></div>
            </div>
        </div>
    </div>

{% endblock %}