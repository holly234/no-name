<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="MYinboxLAB brings every customer conversation into one inbox and gives your team an AI assistant that replies, follows up, and knows when to hand over.">
    <title>MYinboxLAB — Every customer conversation in one place</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=bricolage-grotesque:500,600,700,800|dm-sans:400,500,600,700|manrope:500,600,700,800&display=swap" rel="stylesheet">
    <link rel="preload" as="image" href="{{ asset('images/marketing/perpetual-hero-wide.png') }}" fetchpriority="high">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root{--blue:#2457f5;--blue-dark:#1538bc;--ink:#15191d;--cream:#fff9ed;--lime:#c9f45b;--coral:#ff704f;--pink:#ffb9d1;--sky:#bce7ff;--line:#d8d8d3;--muted:#5f666d}
        [x-cloak]{display:none!important}
        html,body{margin:0;width:100%;max-width:100%;overflow-x:hidden;background:var(--cream);color:var(--ink);font-family:'Bricolage Grotesque','DM Sans',sans-serif}
        *,*::before,*::after{box-sizing:border-box}
        img,svg{display:block;max-width:100%}
        h1,h2,h3,h4,.display,.brand,.mega-product strong,.mega-column a{font-family:'Bricolage Grotesque','Manrope',sans-serif!important}
        a{text-decoration:none;color:inherit}
        button{font:inherit}
        .site{width:100%;overflow:hidden}
        .wrap{width:min(1180px,calc(100% - 40px));margin-inline:auto}
        .display{font-family:'Manrope',Arial,sans-serif;font-weight:800;letter-spacing:-.067em;line-height:.88}
        .eyebrow{font-size:.75rem;font-weight:800;letter-spacing:.13em;text-transform:uppercase}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:.6rem;min-height:54px;padding:0 24px;border-radius:999px;font-weight:800;transition:transform .2s,box-shadow .2s,background .2s}
        .btn:hover{transform:translateY(-2px)}
        .btn-primary{background:var(--blue);color:#fff;box-shadow:0 8px 0 #132f9f}
        .btn-primary:hover{box-shadow:0 5px 0 #132f9f}
        .btn-dark{background:var(--ink);color:#fff;box-shadow:0 8px 0 rgba(0,0,0,.25)}
        .btn-white{background:#fff;color:var(--ink);box-shadow:0 8px 0 rgba(0,0,0,.15)}
        .platform-icon{width:22px;height:22px;flex:0 0 22px}
        header{position:absolute;z-index:20;top:0;left:0;width:100%;color:#fff;transition:background .25s ease,backdrop-filter .25s ease,box-shadow .25s ease}
        header.is-sticky{position:fixed;background:rgba(20,20,20,.82);backdrop-filter:blur(16px);box-shadow:0 1px 0 rgba(255,255,255,.14)}
        header.is-sticky .nav{height:82px}
        header.is-sticky .mega-menu{top:82px}
        header .wrap,.hero .wrap{width:calc(100% - 96px);max-width:none}
        .nav{height:112px;display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:30px}
        .brand{display:flex;align-items:center;font-family:'Manrope',Arial,sans-serif;font-size:1.85rem;font-weight:800;letter-spacing:-.07em;white-space:nowrap}
        .brand-letter{display:inline-block;max-width:1.1em;opacity:1;transform:translateY(0);transition:max-width .62s cubic-bezier(.76,0,.24,1),opacity .42s ease,transform .62s cubic-bezier(.76,0,.24,1)}
        .brand-lab{font-weight:500}
        .brand-mark{display:none}
        .nav-links{display:flex;align-items:center;gap:44px;font-family:'Courier New',monospace;font-size:.92rem;font-weight:700;letter-spacing:.03em;text-transform:uppercase}
        .nav-dropdown-trigger{border:0;padding:0;background:transparent;color:inherit;font:inherit;letter-spacing:inherit;text-transform:inherit;cursor:pointer}
        .mega-menu{position:absolute;z-index:30;top:112px;left:50%;width:min(1760px,calc(100% - 96px));transform:translateX(-50%);padding:50px 8%;border-radius:0 0 8px 8px;background:#fff;color:#111;box-shadow:0 24px 45px rgba(0,0,0,.2)}
        .mega-products{display:grid;grid-template-columns:repeat(5,1fr);gap:30px;text-align:center}.mega-product{display:flex;flex-direction:column;align-items:center;gap:12px}.mega-product .channel-badge{display:grid;place-items:center;width:34px;height:34px;border-radius:9px;background:#eef0f5;color:#2457f5;font-size:1.2rem}.mega-product strong{font-family:'Manrope',Arial,sans-serif;font-size:1.4rem;letter-spacing:-.05em}.mega-product span{max-width:180px;color:#999;font-size:1rem;line-height:1.2;text-transform:none;letter-spacing:0;font-family:Arial,sans-serif;font-weight:400}.mega-columns{display:grid;grid-template-columns:1fr 1fr;gap:25%;max-width:760px;margin:auto}.mega-column{padding-left:25px;border-left:1px solid #ddd}.mega-column:first-child{border-left:0}.mega-label{margin-bottom:27px;color:#999;font-family:'Courier New',monospace;font-size:.82rem;letter-spacing:.06em}.mega-column a{display:block;margin:11px 0;font-family:'Manrope',Arial,sans-serif;font-size:1.55rem;font-weight:800;letter-spacing:-.045em;text-transform:none}.mega-menu a:hover{text-decoration:underline}
        .nav-actions{display:flex;align-items:center;justify-content:flex-end;gap:32px;font-family:'Courier New',monospace;font-size:.92rem;font-weight:700;letter-spacing:.03em;text-transform:uppercase}
        .nav-cta{padding:16px 31px;border:1px solid rgba(255,255,255,.9);border-radius:999px;color:#fff}
        .mobile-toggle{display:none;border:0;background:transparent;padding:8px}
        .menu-icon{position:relative;display:block;width:27px;height:21px}.menu-icon-line{position:absolute;left:1px;width:25px;height:1.5px;background:currentColor;transform-origin:center;transition:top .65s cubic-bezier(.76,0,.24,1),transform .75s cubic-bezier(.76,0,.24,1),width .5s ease}.menu-icon-line:first-child{top:6px}.menu-icon-line:last-child{top:15px}
        .mobile-menu{display:none}
        .hero{position:relative;height:100vh;height:100svh;min-height:720px;padding:0;background:#3b403a url('{{ asset('images/marketing/perpetual-hero-wide.png') }}') center/cover no-repeat;color:#fff}
        .hero::before{content:'';position:absolute;inset:0;background:linear-gradient(90deg,rgba(0,0,0,.82) 0%,rgba(0,0,0,.51) 39%,rgba(0,0,0,.09) 73%),linear-gradient(0deg,rgba(0,0,0,.5),transparent 42%)}
        .hero-grid{position:relative;display:flex;align-items:center;height:100%}
        .hero-copy{position:relative;z-index:2;width:min(790px,55%);padding-top:55px}
        .hero-copy .eyebrow{display:none}
        .hero-copy h1{max-width:790px;margin:0 0 26px;font-size:clamp(5.6rem,7vw,8.25rem);line-height:.83;letter-spacing:-.075em}
        .hero-copy h1 span{color:inherit;white-space:normal}
        .hero-copy h1 span::after{display:none}
        .hero-copy>p{max-width:690px;margin:0 0 43px;font-size:1.42rem;line-height:1.48;color:#fff}
        .hero-actions{display:flex;align-items:center;gap:18px;flex-wrap:wrap}
        .microcopy{display:none}
        .hero .btn-primary{min-width:185px;background:#f200e8;box-shadow:none;font-family:'Courier New',monospace;text-transform:uppercase;letter-spacing:.08em}
        .hero .btn-primary:hover{background:#cf00c7;box-shadow:none}
        .hero .hero-actions .btn:not(.btn-primary){display:none}
        .hero-scroll{position:absolute;z-index:3;bottom:27px;left:50%;display:grid;place-items:center;width:42px;height:42px;border:1px solid rgba(255,255,255,.72);border-radius:50%;transform:translateX(-50%);animation:hero-arrow-bounce 1.8s ease-in-out infinite;color:#fff}.hero-scroll svg{width:20px;height:20px;stroke-width:1.6}@keyframes hero-arrow-bounce{0%,100%{transform:translate(-50%,0)}50%{transform:translate(-50%,9px)}}
        .hero-partners{position:absolute;z-index:3;left:0;bottom:28px;display:flex;align-items:center;gap:35px;color:#fff}
        .partner-mark{display:flex;align-items:center;gap:9px;font-weight:700;line-height:1.02}
        .partner-symbol{font-family:Arial,sans-serif;font-size:2.15rem;font-weight:900;letter-spacing:-.16em}
        .partner-copy{font-size:.92rem}.partner-copy small{display:block;font-size:.68rem;font-weight:800;letter-spacing:.02em;text-transform:uppercase}
        .partner-rating{display:flex;align-items:center;gap:9px}.g2-mark{font-family:Arial,sans-serif;font-size:2.3rem;font-weight:900;letter-spacing:-.12em}.rating-copy{font-size:.76rem;line-height:1.1}.rating-copy strong{display:block;font-size:.92rem}
        .channel-marquee{position:relative;overflow:hidden;border-block:1px solid #d9d9d3;background:#fff;color:#111}
        .channel-marquee::before,.channel-marquee::after{content:'';position:absolute;z-index:2;top:0;bottom:0;width:80px;pointer-events:none}.channel-marquee::before{left:0;background:linear-gradient(90deg,#fff,transparent)}.channel-marquee::after{right:0;background:linear-gradient(-90deg,#fff,transparent)}
        .channel-track{display:flex;width:max-content;animation:channel-marquee 24s linear infinite}.channel-marquee:hover .channel-track{animation-play-state:paused}
        .channel-set{display:flex;align-items:center;flex:none}.channel-logo{display:flex;align-items:center;gap:14px;min-width:250px;height:126px;padding:0 42px;border-right:1px solid #e2e2dd;font-family:'Manrope',Arial,sans-serif;font-size:1.2rem;font-weight:800;white-space:nowrap}.channel-logo .platform-icon{width:30px;height:30px;flex-basis:30px}
        .channel-lead{min-width:330px;font-size:1.35rem;line-height:1.1}.channel-lead b{color:#ff3b38}
        @keyframes channel-marquee{to{transform:translateX(-50%)}}
        .hero-visual{position:relative;min-width:0;padding:30px 26px 28px}
        .hero-blob{position:absolute;inset:0;background:var(--blue);border-radius:47% 53% 45% 55% / 55% 39% 61% 45%;transform:rotate(3deg);box-shadow:18px 18px 0 var(--lime)}
        .hero-photo{position:relative;width:100%;aspect-ratio:4/5;object-fit:cover;object-position:center;border-radius:190px 190px 42px 42px;border:5px solid var(--ink)}
        .float-card{position:absolute;z-index:3;background:#fff;border:2px solid var(--ink);border-radius:18px;box-shadow:7px 7px 0 var(--ink)}
        .message-card{left:-42px;bottom:84px;width:245px;padding:15px}
        .message-card .top{display:flex;align-items:center;gap:10px;margin-bottom:11px;font-weight:800;font-size:.85rem}
        .avatar{display:grid;place-items:center;width:33px;height:33px;border-radius:50%;background:var(--pink);font-size:.76rem}
        .message-card p{margin:0;font-size:.82rem;line-height:1.42}
        .ai-card{right:-36px;top:83px;width:214px;padding:14px;background:var(--lime)}
        .ai-card strong{display:block;margin-bottom:6px;font-size:.75rem;text-transform:uppercase;letter-spacing:.08em}
        .ai-card p{margin:0;font-size:.81rem;line-height:1.4}
        .channel-float{right:6px;bottom:20px;display:flex;align-items:center;gap:8px;padding:11px 14px}
        .channel-list{display:flex;align-items:center;justify-content:flex-end;gap:15px;flex-wrap:wrap}
        .channel-pill{display:flex;align-items:center;gap:8px;padding:10px 13px;border:1px solid var(--line);border-radius:999px;background:#fff;font-weight:700;font-size:.85rem}
        .social-proof{padding:100px 0 104px;background:#fff}
        .proof-head{display:flex;align-items:end;justify-content:space-between;gap:40px;margin-bottom:45px}
        .proof-head h2{max-width:720px;margin:8px 0 0;font-size:clamp(2.7rem,5vw,4.9rem)}
        .proof-count{font-family:'Manrope',sans-serif;font-size:1.05rem;font-weight:800;color:var(--blue)}
        .testimonials{display:grid;grid-template-columns:1.1fr .9fr .9fr;gap:18px}
        .quote{min-height:285px;padding:28px;border:2px solid var(--ink);border-radius:26px;display:flex;flex-direction:column;justify-content:space-between}
        .quote:nth-child(1){background:var(--sky)}.quote:nth-child(2){background:var(--pink);transform:rotate(1deg)}.quote:nth-child(3){background:var(--lime);transform:rotate(-1deg)}
        .quote blockquote{margin:0;font-family:'Manrope',sans-serif;font-size:1.35rem;font-weight:700;line-height:1.28;letter-spacing:-.025em}
        .person{display:flex;align-items:center;gap:11px;font-size:.85rem}.person .avatar{border:1.5px solid var(--ink);background:#fff}.person strong{display:block}.person span{color:#52595e}
        .features{padding:116px 0;background:var(--ink);color:#fff}
        .section-center{text-align:center;max-width:830px;margin:0 auto 58px}.section-center h2{margin:13px 0 20px;font-size:clamp(3rem,5.5vw,5.5rem)}.section-center p{margin:0 auto;max-width:650px;color:#b7bdc1;font-size:1.08rem;line-height:1.65}
        .feature-stage{display:grid;grid-template-columns:.82fr 1.18fr;gap:50px;align-items:center}
        .feature-list{display:grid;gap:14px}
        .feature-item{padding:22px;border:1px solid #3b4147;border-radius:18px;background:#20252a}
        .feature-item.active{background:var(--blue);border-color:var(--blue)}
        .feature-item h3{margin:0 0 7px;font-family:'Manrope',sans-serif;font-size:1.17rem}.feature-item p{margin:0;color:#bfc5c9;font-size:.92rem;line-height:1.5}.feature-item.active p{color:#dfe6ff}
        .inbox-window{overflow:hidden;border-radius:24px;background:#f7f7f3;color:var(--ink);box-shadow:15px 15px 0 var(--coral)}
        .window-bar{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #ddd;background:#fff}.dots{display:flex;gap:6px}.dots i{width:9px;height:9px;border-radius:50%;background:#c7c7c1}
        .inbox-body{display:grid;grid-template-columns:42% 58%;min-height:440px}.conversations{border-right:1px solid #ddd;background:#fff}.inbox-search{margin:15px;padding:12px 14px;border:1px solid #ddd;border-radius:11px;color:#8b9094;font-size:.8rem}
        .conversation{padding:16px;border-top:1px solid #eee}.conversation.active{background:#e9efff;border-left:4px solid var(--blue)}.conversation strong{font-size:.85rem}.conversation p{margin:5px 0 8px;color:#6c7378;font-size:.75rem}.tag{display:inline-flex;padding:4px 8px;border-radius:999px;background:#eaf8c9;color:#405311;font-size:.65rem;font-weight:800}
        .chat{display:flex;flex-direction:column;background:#f5f4ef}.chat-head{padding:16px 18px;background:#fff;border-bottom:1px solid #ddd;font-weight:800}.chat-messages{display:flex;flex:1;flex-direction:column;gap:13px;padding:21px}.bubble{max-width:82%;padding:12px 14px;border-radius:15px;font-size:.78rem;line-height:1.45}.bubble.customer{align-self:flex-start;background:#fff}.bubble.ai{align-self:flex-end;background:var(--blue);color:#fff}.bubble-label{font-size:.62rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;opacity:.75}.composer{margin:0 16px 16px;padding:13px 14px;border:1px solid #ddd;border-radius:12px;background:#fff;color:#9ba0a4;font-size:.76rem}
        .before-after{padding:140px 0;background:#fff;color:#111}
        .before-after>.wrap>.eyebrow{display:flex;align-items:center;justify-content:center;text-align:center;color:#ff684d!important}.before-after>.wrap>.eyebrow::before{display:none}.before-after>.wrap>.eyebrow span{display:none}.before-after>.wrap>.eyebrow .accent-icon{width:86px;height:86px;padding:19px;color:#ff684d;stroke-width:1.65;border:2px solid #ff684d;border-radius:50%;background:#fff7f4}
        .illustration-picker{display:grid;grid-template-columns:repeat(10,1fr);gap:10px;max-width:900px;margin:28px auto 54px}.illustration-option{display:flex;min-height:92px;flex-direction:column;align-items:center;justify-content:center;gap:8px;border:1px solid #e1e1dc;border-radius:16px;background:#fff;cursor:pointer;color:#222;transition:transform .2s,box-shadow .2s}.illustration-option:hover{transform:translateY(-4px);box-shadow:0 8px 0 #ecece6}.illustration-option svg{width:30px;height:30px;stroke:var(--option-color);stroke-width:1.7}.illustration-option small{font-size:.64rem;font-weight:700;color:#666}.illustration-option:nth-child(1){--option-color:#2457f5}.illustration-option:nth-child(2){--option-color:#ff704f}.illustration-option:nth-child(3){--option-color:#7a45d1}.illustration-option:nth-child(4){--option-color:#159b69}.illustration-option:nth-child(5){--option-color:#111}.illustration-option:nth-child(6){--option-color:#e34b7a}.illustration-option:nth-child(7){--option-color:#e39a24}.illustration-option:nth-child(8){--option-color:#1688b7}.illustration-option:nth-child(9){--option-color:#705b3b}.illustration-option:nth-child(10){--option-color:#8c4b9e}
        .before-after>.wrap>.eyebrow{display:flex}.before-after>.wrap>.eyebrow .accent-icon{color:#159b69;border-color:#159b69;background:#f0fbf5}
        .before-after>.wrap>.illustration-picker{display:none}
        .faq{padding:130px 0 145px;background:#080808;color:#fff}.faq-head{text-align:center}.faq-icon{display:grid;place-items:center;width:72px;height:72px;margin:0 auto 28px;border-radius:22px 22px 22px 5px;background:#c9f45b;color:#111;transform:rotate(-4deg)}.faq-icon svg{width:34px;height:34px;stroke-width:1.8}.faq h2{max-width:970px;margin:0 auto;font-size:clamp(4rem,7vw,7.5rem);line-height:.82;letter-spacing:-.075em}.faq-list{max-width:1020px;margin:100px auto 0;border-top:1px solid #353535}.faq-item{border-bottom:1px solid #353535}.faq-question{display:grid;width:100%;grid-template-columns:1fr auto;align-items:center;gap:30px;padding:29px 0;border:0;background:transparent;color:#fff;text-align:left;cursor:pointer;font-size:clamp(1.15rem,1.7vw,1.55rem);font-weight:600}.faq-toggle{position:relative;width:28px;height:28px}.faq-toggle::before,.faq-toggle::after{content:'';position:absolute;top:13px;left:3px;width:22px;height:1.5px;background:#fff;transition:transform .45s cubic-bezier(.76,0,.24,1)}.faq-toggle::after{transform:rotate(90deg)}.faq-question[aria-expanded="true"] .faq-toggle::after{transform:rotate(0)}.faq-answer{display:grid;grid-template-rows:0fr;transition:grid-template-rows .5s cubic-bezier(.76,0,.24,1)}.faq-answer.open{grid-template-rows:1fr}.faq-answer-clip{min-height:0;overflow:hidden}.faq-answer-inner{max-width:800px;padding:0 60px 28px 0;color:#bcbcbc;font-size:1.04rem;line-height:1.65}
        .before-after h2{max-width:1020px;margin:18px auto 18px;text-align:center;font-family:'Bricolage Grotesque','Manrope',sans-serif;font-size:clamp(4rem,7.3vw,8rem);font-weight:800;line-height:.82;letter-spacing:-.082em}.before-after h2::after{content:'Same workload. A completely different day.';display:block;margin:30px auto 62px;font-family:'Bricolage Grotesque','DM Sans',sans-serif;font-size:1.15rem;font-weight:500;letter-spacing:-.02em;line-height:1.4;color:#60636a}
        .compare-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px}
        .compare{min-height:610px;padding:48px 40px 38px;border:0;border-radius:38px;display:flex;flex-direction:column}.compare.before{background:#f0f2ef}.compare.after{background:#33205f;color:#fff;box-shadow:none}.compare-label{font-weight:800;text-align:center;text-transform:uppercase;letter-spacing:.1em;font-size:.72rem}.compare.before .compare-label{font-size:0}.compare.before .compare-label::after{content:'Before MYinboxLAB';font-size:.72rem}.compare.after .compare-label{font-size:0}.compare.after .compare-label::after{content:'With MYinboxLAB';font-size:.72rem}.compare h3{max-width:520px;margin:22px auto auto;text-align:center;font-family:'Manrope',sans-serif;font-size:clamp(2.8rem,4.2vw,4.7rem);font-weight:800;line-height:.9;letter-spacing:-.065em}.compare ul{list-style:none;margin:55px 0 0;padding:0;display:grid}.compare li{display:flex;align-items:center;gap:13px;padding:17px 0;border-top:1px solid rgba(0,0,0,.14);font-family:'Courier New',monospace;font-size:.87rem;font-weight:700;line-height:1.35;text-transform:uppercase}.compare.after li{border-color:rgba(255,255,255,.2)}.bullet{display:grid;place-items:center;order:2;margin-left:auto;flex:0 0 25px;width:25px;height:25px;border:0;border-radius:7px;background:#111;color:#fff;font-family:Arial,sans-serif;font-weight:800}.compare.after .bullet{background:#fff;color:#33205f}
        .steps{padding:112px 0;background:var(--blue);color:#fff}.steps-head{display:flex;align-items:end;justify-content:space-between;gap:35px;margin-bottom:56px}.steps-head h2{max-width:700px;margin:12px 0 0;font-size:clamp(3rem,5.2vw,5.2rem)}.steps-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}.step{position:relative;min-height:300px;padding:30px;border:2px solid #fff;border-radius:28px}.step:nth-child(1){background:#fff;color:var(--ink)}.step:nth-child(2){background:var(--pink);color:var(--ink);transform:translateY(22px)}.step:nth-child(3){background:var(--lime);color:var(--ink)}.step-number{display:grid;place-items:center;width:50px;height:50px;border:2px solid currentColor;border-radius:50%;font-family:'Manrope',sans-serif;font-size:1.25rem;font-weight:800}.step h3{margin:60px 0 10px;font-family:'Manrope',sans-serif;font-size:1.65rem;letter-spacing:-.03em}.step p{margin:0;line-height:1.55;color:#4f565b}
        .ai-section{padding:120px 0;background:#fff}.ai-grid{display:grid;grid-template-columns:1fr 1fr;gap:70px;align-items:center}.ai-copy h2{margin:13px 0 24px;font-size:clamp(3rem,5vw,5.2rem)}.ai-copy>p{margin:0 0 29px;color:var(--muted);font-size:1.08rem;line-height:1.65}.checks{display:grid;gap:13px;margin-bottom:32px}.check{display:flex;align-items:center;gap:11px;font-weight:700}.check i{display:grid;place-items:center;width:25px;height:25px;border-radius:50%;background:var(--lime);font-style:normal}
        .phone-demo{position:relative;width:min(420px,100%);margin:auto;padding:22px;border:3px solid var(--ink);border-radius:45px;background:#fff;box-shadow:18px 18px 0 var(--pink)}.phone-top{width:100px;height:22px;margin:-9px auto 18px;border-radius:999px;background:var(--ink)}.phone-chat{padding:17px;border-radius:26px;background:#f0f2f5}.phone-chat .bubble{font-size:.86rem;margin-bottom:12px}.voice{display:flex;align-items:center;gap:9px}.play{display:grid;place-items:center;width:28px;height:28px;border-radius:50%;background:var(--blue);color:#fff}.wave{flex:1;height:23px;background:repeating-linear-gradient(90deg,#87909a 0 2px,transparent 2px 6px);mask-image:linear-gradient(to bottom,transparent 10%,#000 10% 90%,transparent 90%)}
        .final-cta{padding:115px 0;background:var(--coral);text-align:center}.final-cta h2{max-width:900px;margin:0 auto 24px;font-size:clamp(3.5rem,7vw,7rem)}.final-cta p{max-width:580px;margin:0 auto 32px;font-size:1.1rem;line-height:1.6}.final-actions{display:flex;justify-content:center;gap:16px;flex-wrap:wrap}
        .footer{padding:65px 0 32px;background:var(--ink);color:#fff}.footer-top{display:flex;justify-content:space-between;gap:50px;padding-bottom:54px}.footer-brand{max-width:330px}.footer-brand p{color:#9ea5aa;line-height:1.6}.footer-links{display:grid;grid-template-columns:repeat(3,1fr);gap:60px}.footer-links strong{display:block;margin-bottom:15px}.footer-links a{display:block;margin:9px 0;color:#aeb4b8;font-size:.9rem}.footer-bottom{display:flex;justify-content:space-between;gap:20px;padding-top:25px;border-top:1px solid #383e43;color:#888f94;font-size:.8rem}
        .footer-legacy{display:none}.footer{padding:74px 0 28px;background:#050505;color:#fff}.footer-top{display:grid;grid-template-columns:minmax(420px,1fr) minmax(420px,.9fr);gap:80px;align-items:start;padding-bottom:88px}.footer-brand{max-width:430px}.footer-brand .brand{font-size:clamp(2.8rem,4vw,4.8rem);letter-spacing:-.08em}.footer-brand p{max-width:320px;margin:34px 0 0;color:#a7a7a7;line-height:1.6}.footer-links{display:grid;grid-template-columns:repeat(3,1fr);gap:34px}.footer-links strong{display:block;margin-bottom:18px;color:#777;font-size:.72rem;letter-spacing:.12em;text-transform:uppercase}.footer-links a{display:block;margin:11px 0;color:#f1f1f1;font-size:.92rem}.footer-links a:hover{color:var(--lime)}.footer-art{position:relative;min-height:360px}.footer-art img{position:absolute;right:-8%;top:-50px;width:min(690px,120%);max-width:none}.footer-tagline{position:absolute;left:0;bottom:18px;max-width:280px;font-size:clamp(2.2rem,3.5vw,4rem);font-weight:800;line-height:.86;letter-spacing:-.07em}.footer-social{display:flex;gap:14px;margin-top:27px}.footer-social a{display:grid;place-items:center;width:34px;height:34px;border:1px solid #555;border-radius:50%;color:#fff}.footer-social svg{width:16px;height:16px}.footer-bottom{display:flex;justify-content:space-between;gap:20px;padding-top:25px;border-top:1px solid #292929;color:#777;font-size:.78rem}.footer-bottom strong{color:#fff}
        @media(max-width:720px){.footer{padding:54px 0 24px}.footer-top{display:flex;flex-direction:column;gap:45px;padding-bottom:55px}.footer-brand .brand{font-size:2.8rem}.footer-links{grid-template-columns:repeat(2,1fr);gap:28px}.footer-art{width:100%;min-height:270px}.footer-art img{right:-12%;top:-15px;width:125%}.footer-tagline{left:0;bottom:0;font-size:2.4rem}.footer-bottom{align-items:flex-start;flex-direction:column;gap:9px}}
        @media(max-width:960px){.nav{grid-template-columns:1fr auto}.nav-links{display:none}.mega-menu{width:calc(100% - 32px)}.hero-copy{width:min(680px,70%)}.feature-stage,.ai-grid{grid-template-columns:1fr}.feature-list{grid-template-columns:repeat(3,1fr)}.feature-item{padding:16px}.testimonials{grid-template-columns:1fr 1fr}.quote:first-child{grid-column:1/-1}.footer-top{flex-direction:column}.inbox-window{width:min(760px,100%);margin:auto}}
        @media(max-width:720px){.wrap{width:min(100% - 32px,1180px)}header .wrap,.hero .wrap{width:calc(100% - 40px)}header{z-index:60}.nav{position:relative;z-index:62;height:72px;grid-template-columns:1fr auto;gap:12px}.nav-actions,.nav-links{display:none}.brand{font-size:1.25rem;letter-spacing:-.065em}.mobile-toggle{display:grid;place-items:center;color:#fff;cursor:pointer}.menu-open .mobile-toggle{color:#111}.menu-open .menu-icon-line:first-child{top:10px;transform:rotate(225deg)}.menu-open .menu-icon-line:last-child{top:10px;transform:rotate(-225deg)}.menu-open header{color:#111}.menu-open header.is-sticky{background:transparent;backdrop-filter:none;box-shadow:none}.menu-open .brand-letter:not(.brand-keep){max-width:0;opacity:0;transform:translateY(-5px);overflow:hidden}.menu-open .brand-keep{text-transform:uppercase}.menu-open .brand-letter:nth-child(10){transition-delay:.04s}.menu-open .brand-letter:nth-child(9){transition-delay:.1s}.menu-open .brand-letter:nth-child(8){transition-delay:.16s}.menu-open .brand-letter:nth-child(7){transition-delay:.22s}.menu-open .brand-letter:nth-child(6){transition-delay:.28s}.menu-open .brand-letter:nth-child(5){transition-delay:.34s}.menu-open .brand-letter:nth-child(4){transition-delay:.4s}.menu-open .brand-letter:nth-child(3){transition-delay:.46s}.menu-open .brand-letter:nth-child(2){transition-delay:.52s}.mobile-menu{position:fixed;z-index:61;inset:0;display:flex;height:100dvh;padding:92px 20px 28px;flex-direction:column;background:#eef2ee;color:#111;overflow-y:auto;transform-origin:top}.mobile-menu.menu-sweep-enter-active{transition:clip-path .95s cubic-bezier(.76,0,.24,1)}.mobile-menu.menu-sweep-enter-start{clip-path:inset(0 0 100% 0)}.mobile-menu.menu-sweep-enter-end{clip-path:inset(0 0 0 0)}.mobile-menu.menu-sweep-leave-active{transition:clip-path .7s cubic-bezier(.76,0,.24,1)}.mobile-menu.menu-sweep-leave-start{clip-path:inset(0 0 0 0)}.mobile-menu.menu-sweep-leave-end{clip-path:inset(0 0 100% 0)}.mobile-menu-primary,.mobile-menu-links{transition:opacity .55s ease .38s,transform .7s cubic-bezier(.22,1,.36,1) .3s}.mobile-menu.menu-sweep-enter-start .mobile-menu-primary,.mobile-menu.menu-sweep-enter-start .mobile-menu-links{opacity:0;transform:translateY(-18px)}.mobile-menu-primary{display:flex;flex-direction:column;align-items:center;gap:18px;margin:2px 0 38px}.mobile-menu .mobile-cta{display:inline-flex;padding:15px 31px;border-radius:999px;background:#f200e8;color:#fff;font-family:'Courier New',monospace;font-weight:700;text-transform:uppercase;letter-spacing:.06em}.mobile-menu .mobile-login{font-family:'Courier New',monospace;font-size:.82rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em}.mobile-menu-links{display:grid;gap:6px}.mobile-menu-links a{display:flex;align-items:center;justify-content:space-between;min-height:54px;padding:0 18px;border-radius:9px;background:#fff;font-family:'Courier New',monospace;font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.03em}.mobile-menu-links a::after{content:'▾';font-size:.9rem}.mobile-menu-links a[href*="login"]::after{display:none}.hero{height:100svh;min-height:680px;background-position:64% center}.hero::before{background:linear-gradient(180deg,rgba(0,0,0,.7) 0%,rgba(0,0,0,.31) 52%,rgba(0,0,0,.54) 100%)}.hero-grid{align-items:flex-start;padding-top:96px}.hero-copy{width:100%;padding-top:0}.hero-copy h1{max-width:340px;margin-bottom:14px;font-size:clamp(3rem,13vw,4.15rem);line-height:.86;letter-spacing:-.075em}.hero-copy>p{max-width:355px;margin-bottom:22px;font-size:.96rem;line-height:1.28}.hero-actions{display:block}.hero-actions .btn{width:auto;min-width:143px;padding:14px 21px;font-size:.78rem}.hero-partners{gap:17px;bottom:19px}.partner-copy{font-size:.68rem}.partner-copy small{font-size:.53rem}.partner-symbol{font-size:1.55rem}.g2-mark{font-size:1.7rem}.rating-copy{font-size:.58rem}.rating-copy strong{font-size:.7rem}.hero-scroll{display:none}.story-intro{min-height:70vh;padding:90px 0}.story-intro h2{font-size:clamp(3rem,14vw,5rem)}.story-intro p{font-size:1.05rem}.story-sticky{min-height:680px}.story-progress{top:35px}.story-panel{grid-template-columns:1fr;width:calc(100% - 32px);gap:24px;padding-top:65px}.story-copy h3{font-size:clamp(3rem,13vw,4.8rem)}.story-copy p{font-size:1.03rem}.story-card{width:min(340px,100%)}.story-panel .story-cta{margin-top:20px}.trust-row,.proof-head,.steps-head{align-items:flex-start;flex-direction:column}.channel-list{justify-content:flex-start}.trust-row>p{white-space:normal}.social-proof,.features,.before-after,.steps,.ai-section,.final-cta{padding-block:80px}.testimonials,.compare-grid,.steps-grid{grid-template-columns:1fr}.quote:first-child{grid-column:auto}.quote:nth-child(2),.quote:nth-child(3),.step:nth-child(2){transform:none}.feature-list{grid-template-columns:1fr}.inbox-body{grid-template-columns:1fr}.conversations{display:none}.chat{min-height:420px}.compare{padding:27px}.steps-grid{gap:16px}.step{min-height:250px}.step h3{margin-top:45px}.ai-grid{gap:55px}.footer-links{grid-template-columns:1fr 1fr;gap:35px}.footer-bottom{flex-direction:column}.float-card{box-shadow:4px 4px 0 var(--ink)}}
        @media(max-width:720px){.menu-open{height:100dvh;overflow:hidden}header.is-sticky .nav{height:72px}}
        @media(max-width:720px){.hero-scroll{display:grid;bottom:24px;width:38px;height:38px}}
        @media(max-width:720px){.illustration-picker{grid-template-columns:repeat(5,1fr);gap:8px;margin-bottom:42px}.illustration-option{min-height:76px;border-radius:12px}.illustration-option svg{width:25px;height:25px}.illustration-option small{font-size:.56rem}}
        @media(max-width:720px){.faq{padding:85px 0 95px}.faq-icon{width:60px;height:60px;margin-bottom:24px}.faq-icon svg{width:28px;height:28px}.faq h2{font-size:clamp(3.5rem,15vw,5.2rem)}.faq-list{margin-top:65px}.faq-question{padding:23px 0;font-size:1.08rem;line-height:1.25}.faq-answer-inner{padding-right:25px;font-size:.95rem}}
        /* Marketing typography system: one expressive family from header through footer. */
        .site,.site *{font-family:'Bricolage Grotesque','DM Sans',sans-serif!important}
        .mobile-menu-group>button,.mobile-menu-direct{font-family:'Bricolage Grotesque','DM Sans',sans-serif!important;font-size:1rem;letter-spacing:-.02em}
        .mobile-menu-group{background:#fff;border-radius:9px;overflow:hidden}.mobile-menu-group+.mobile-menu-group{margin-top:6px}.mobile-menu-group>button,.mobile-menu-direct{display:flex;width:100%;min-height:54px;align-items:center;justify-content:space-between;padding:0 18px;border:0;background:#fff;color:#111;font-family:'Courier New',monospace!important;font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.03em;text-align:left;cursor:pointer}.mobile-menu-group>button::after{content:'▾';font-family:Arial,sans-serif;font-size:.9rem;transition:transform .35s}.mobile-menu-group>button[aria-expanded="true"]::after{transform:rotate(180deg)}.mobile-submenu{display:grid;gap:0;padding:0 18px 12px}.mobile-submenu a{display:flex!important;min-height:35px!important;align-items:center!important;justify-content:flex-start!important;gap:10px;padding:4px 0!important;background:transparent!important;font-family:'Bricolage Grotesque','DM Sans',sans-serif!important;font-size:1.02rem!important;font-weight:700!important;text-transform:none!important;letter-spacing:-.02em!important}.mobile-submenu a::after{display:none!important}.mobile-submenu .platform-icon{width:20px;height:20px;flex:0 0 20px}
        .footer-social .platform-icon{width:20px;height:20px;color:#fff!important}.footer-social .platform-icon svg{width:20px;height:20px}
        .footer .wrap{width:calc(100% - 96px);max-width:none}.footer-top{display:block!important;padding-bottom:88px}.footer-brand{max-width:430px}.footer-brand .brand{font-size:clamp(3rem,5vw,5.8rem)}.footer-brand p{max-width:390px;font-size:1.05rem}.footer-art{display:none!important}.footer-links{display:grid!important;grid-template-columns:repeat(4,1fr)!important;gap:40px!important;margin-top:92px!important}.footer-links strong{color:#777}.footer-links a{font-size:1rem;margin:13px 0}.footer-bottom{font-size:.8rem}
        @media(max-width:720px){.footer .wrap{width:calc(100% - 40px)}.footer-links{grid-template-columns:repeat(2,1fr)!important;margin-top:58px!important;gap:30px!important}.footer-brand .brand{font-size:3rem}}
        @media(max-width:720px){
            .menu-open .brand{color:#111;transition:none}
            .menu-open .brand-letter{transition-duration:1.05s}
            .menu-open .brand-letter:nth-child(10){transition-delay:.08s}
            .menu-open .brand-letter:nth-child(9){transition-delay:.18s}
            .menu-open .brand-letter:nth-child(8){transition-delay:.28s}
            .menu-open .brand-letter:nth-child(7){transition-delay:.38s}
            .menu-open .brand-letter:nth-child(6){transition-delay:.48s}
            .menu-open .brand-letter:nth-child(5){transition-delay:.58s}
            .menu-open .brand-letter:nth-child(4){transition-delay:.68s}
            .menu-open .brand-letter:nth-child(3){transition-delay:.78s}
            .menu-open .brand-letter:nth-child(2){transition-delay:.88s}
        }
        @media(max-width:420px){.wrap{width:calc(100% - 24px)}header .wrap,.hero .wrap{width:calc(100% - 32px)}.hero-copy h1{font-size:3.2rem}.footer-links{grid-template-columns:1fr}.channel-pill{font-size:.76rem}}
        .mobile-menu-links .mobile-menu-group>button,.mobile-menu-links .mobile-menu-direct{font-family:'Bricolage Grotesque','DM Sans',sans-serif!important;font-size:.95rem!important;font-weight:500!important;letter-spacing:-.01em!important}
    </style>
</head>
@php
    $primaryUrl = auth()->check() ? route('dashboard') : route('auth.google.redirect');
    $primaryLabel = auth()->check() ? 'Open workspace' : 'Get started';
    $channels = [['Instagram','instagram'],['WhatsApp','whatsapp'],['Facebook','facebook'],['Gmail','gmail'],['Telegram','telegram']];
@endphp
<body>
<div class="site" x-data="{ menu: false, dropdown: null, scrolled: false }" :class="{ 'menu-open': menu }">
    <header x-init="scrolled = window.scrollY > 40" :class="{ 'is-sticky': scrolled }" @scroll.window="scrolled = window.scrollY > 40" @mouseleave="dropdown = null">
        <div class="wrap">
            <nav class="nav" aria-label="Main navigation">
                <a class="brand" href="/" aria-label="MYinboxLAB">
                    <span class="brand-letter brand-first brand-keep">M</span><span class="brand-letter">Y</span><span class="brand-letter brand-keep">i</span><span class="brand-letter">n</span><span class="brand-letter">b</span><span class="brand-letter">o</span><span class="brand-letter">x</span><span class="brand-letter brand-lab">L</span><span class="brand-letter brand-lab">A</span><span class="brand-letter brand-lab brand-keep">B</span>
                </a>
                <div class="nav-links">
                    <button class="nav-dropdown-trigger" @mouseenter="dropdown = 'product'" @click="dropdown = dropdown === 'product' ? null : 'product'" :aria-expanded="dropdown === 'product'">Product</button>
                    <button class="nav-dropdown-trigger" @mouseenter="dropdown = 'solutions'" @click="dropdown = dropdown === 'solutions' ? null : 'solutions'" :aria-expanded="dropdown === 'solutions'">Solutions</button>
                    <a href="#how">How it works</a>
                    <button class="nav-dropdown-trigger" @mouseenter="dropdown = 'resources'" @click="dropdown = dropdown === 'resources' ? null : 'resources'" :aria-expanded="dropdown === 'resources'">Resources</button>
                </div>
                <div class="nav-actions">@auth<a href="{{ route('dashboard') }}">Dashboard</a>@else<a href="{{ route('login') }}">Log in</a>@endauth<a class="nav-cta" href="{{ $primaryUrl }}">Get started</a></div>
                <button class="mobile-toggle" type="button" @click="menu=!menu" :aria-expanded="menu" :aria-label="menu ? 'Close menu' : 'Open menu'">
                    <span class="menu-icon" aria-hidden="true"><span class="menu-icon-line"></span><span class="menu-icon-line"></span></span>
                </button>
            </nav>
            <div class="mega-menu" x-show="dropdown === 'product'" x-cloak @click.outside="dropdown=null">
                <div class="mega-products">
                    <a class="mega-product" href="{{ route('marketing.instagram') }}"><span class="channel-badge"><span class="platform-icon" data-platform-icon="instagram"></span></span><strong>Instagram</strong><span>Automate your Instagram conversations</span></a>
                    <a class="mega-product" href="{{ route('marketing.whatsapp') }}"><span class="channel-badge" style="color:#18bf54"><span class="platform-icon" data-platform-icon="whatsapp"></span></span><strong>WhatsApp</strong><span>Connect with customers instantly</span></a>
                    <a class="mega-product" href="{{ route('marketing.facebook') }}"><span class="channel-badge"><span class="platform-icon" data-platform-icon="facebook"></span></span><strong>Messenger</strong><span>Bring Facebook messages together</span></a>
                    <a class="mega-product" href="#product"><span class="channel-badge" style="background:#111;color:#fff">♪</span><strong>TikTok</strong><span>Turn conversations into momentum</span></a>
                    <a class="mega-product" href="#ai"><span class="channel-badge" style="background:#ff6c4f;color:#fff">✦</span><strong>MYinboxLAB AI</strong><span>A smarter way to handle your inbox</span></a>
                </div>
            </div>
            <div class="mega-menu" x-show="dropdown === 'solutions'" x-cloak @click.outside="dropdown=null">
                <div class="mega-columns"><div class="mega-column"><div class="mega-label">BY BUSINESS TYPE</div><a href="#customers">For service businesses</a><a href="#customers">For online stores</a><a href="#customers">For growing teams</a></div><div class="mega-column"><div class="mega-label">BY USE CASE</div><a href="#product">Customer support</a><a href="#ai">AI replies</a><a href="#how">Team inbox</a></div></div>
            </div>
            <div class="mega-menu" x-show="dropdown === 'resources'" x-cloak @click.outside="dropdown=null">
                <div class="mega-columns"><div class="mega-column"><div class="mega-label">LEARN</div><a href="#ai">AI inbox guide</a><a href="#how">Getting started</a><a href="#product">Help centre</a></div><div class="mega-column"><div class="mega-label">GET INSPIRED</div><a href="#customers">Customer stories</a><a href="#product">Product updates</a><a href="#how">Best practices</a></div></div>
            </div>
            <div class="mobile-menu" x-show="menu" x-cloak
                 x-transition:enter="menu-sweep-enter-active"
                 x-transition:enter-start="menu-sweep-enter-start"
                 x-transition:enter-end="menu-sweep-enter-end"
                 x-transition:leave="menu-sweep-leave-active"
                 x-transition:leave-start="menu-sweep-leave-start"
                 x-transition:leave-end="menu-sweep-leave-end">
                <div class="mobile-menu-primary">
                    <a class="mobile-cta" href="{{ $primaryUrl }}">Get started</a>
                    @auth<a class="mobile-login" href="{{ route('dashboard') }}">Dashboard</a>@else<a class="mobile-login" href="{{ route('login') }}">Sign in</a>@endauth
                </div>
                <div class="mobile-menu-links">
                    <div class="mobile-menu-group" x-data="{ open: true }"><button type="button" @click="open=!open" :aria-expanded="open">Product</button><div class="mobile-submenu" x-show="open" x-transition><a href="{{ route('marketing.instagram') }}" @click="menu=false"><span class="platform-icon" data-platform-icon="instagram"></span>Instagram</a><a href="#faq" @click="menu=false"><span class="platform-icon" data-platform-icon="whatsapp"></span>WhatsApp</a><a href="#faq" @click="menu=false"><span class="platform-icon" data-platform-icon="facebook"></span>Messenger</a><a href="#faq" @click="menu=false"><span class="platform-icon" data-platform-icon="telegram"></span>Telegram</a><a href="#faq" @click="menu=false"><span class="platform-icon" data-platform-icon="gmail"></span>Gmail</a></div></div>
                    <div class="mobile-menu-group" x-data="{ open: false }"><button type="button" @click="open=!open" :aria-expanded="open">Solutions</button><div class="mobile-submenu" x-show="open" x-transition><a href="#faq" @click="menu=false">Customer support</a><a href="#faq" @click="menu=false">AI replies</a><a href="#faq" @click="menu=false">Team inbox</a></div></div>
                    <div class="mobile-menu-group" x-data="{ open: false }"><button type="button" @click="open=!open" :aria-expanded="open">Agencies</button><div class="mobile-submenu" x-show="open" x-transition><a href="#faq" @click="menu=false">For growing teams</a><a href="#faq" @click="menu=false">Partner support</a></div></div>
                    <a class="mobile-menu-direct" href="#faq" @click="menu=false">Pricing</a>
                    <div class="mobile-menu-group" x-data="{ open: false }"><button type="button" @click="open=!open" :aria-expanded="open">Resources</button><div class="mobile-submenu" x-show="open" x-transition><a href="#faq" @click="menu=false">Help centre</a><a href="#faq" @click="menu=false">Getting started</a><a href="#faq" @click="menu=false">Conversation guide</a></div></div>
                </div>
            </div>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="wrap hero-grid">
                <div class="hero-copy">
                    <div class="eyebrow">One inbox. Every customer.</div>
                    <h1 class="display">Every inbox.<br><span>One smart team.</span></h1>
                    <p>Reply faster, stay organised, and grow every customer relationship from one AI-powered inbox.</p>
                    <div class="hero-actions"><a class="btn btn-primary" href="{{ $primaryUrl }}">{{ $primaryLabel }} <span>→</span></a><a class="btn" href="#product">See it in action ↓</a></div>
                    <div class="microcopy">No card required · Set up in minutes · Human takeover built in</div>
                </div>
                <div class="hero-partners" aria-label="Platform partner and rating marks">
                    <div class="partner-mark"><span class="partner-symbol">∞</span><span class="partner-copy">Meta Business<br>Partners</span></div>
                </div>
                <a class="hero-scroll" href="#faq" aria-label="Scroll to the FAQ"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M5 9.5 12 16l7-6.5"/></svg></a>
            </div>
        </section>

        <section class="channel-marquee" aria-label="Channels supported by MYinboxLAB">
            <div class="channel-track">
                @foreach([1, 2] as $repeat)
                    <div class="channel-set" @if($repeat === 2) aria-hidden="true" @endif>
                        <div class="channel-logo channel-lead"><b>♥</b><span>Every message.<br>One moving inbox.</span></div>
                        @foreach($channels as [$name, $icon])
                            <div class="channel-logo"><span class="platform-icon" data-platform-icon="{{ $icon }}"></span><span>{{ $name }}</span></div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </section>

        <section class="before-after">
            <div class="wrap">
                <div class="eyebrow" style="color:#159b69"><svg class="accent-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M4 7h16M4 12h10M4 17h7"/><path d="m17 14 3 3-3 3M20 17h-7"/></svg><span>Your inbox: before &amp; after</span></div>
                <div class="illustration-picker" aria-label="Illustration options">
                    <button class="illustration-option" type="button"><svg viewBox="0 0 24 24" fill="none"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3h11A2.5 2.5 0 0 1 20 5.5v9a2.5 2.5 0 0 1-2.5 2.5h-6l-4.2 3v-3H6.5A2.5 2.5 0 0 1 4 14.5v-9Z"/><path d="m8.5 11 2.1 2.1 4.9-5"/></svg><small>Inbox</small></button>
                    <button class="illustration-option" type="button"><svg viewBox="0 0 24 24" fill="none"><path d="M20 11.5a8 8 0 0 1-8 8 8.8 8.8 0 0 1-3.4-.7L4 20l1.2-4.2A8 8 0 1 1 20 11.5Z"/><path d="M8 11h.01M12 11h.01M16 11h.01"/></svg><small>Messages</small></button>
                    <button class="illustration-option" type="button"><svg viewBox="0 0 24 24" fill="none"><path d="m12 3 1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z"/><path d="m19 15 .7 2.3L22 18l-2.3.7L19 21l-.7-2.3L16 18l2.3-.7L19 15Z"/></svg><small>Sparkles</small></button>
                    <button class="illustration-option" type="button"><svg viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h10M4 17h7"/><path d="m17 14 3 3-3 3M20 17h-7"/></svg><small>Workflow</small></button>
                    <button class="illustration-option" type="button"><svg viewBox="0 0 24 24" fill="none"><rect x="4" y="4" width="16" height="16" rx="3"/><path d="m8 12 2.5 2.5L16 9"/></svg><small>Sorted</small></button>
                    <button class="illustration-option" type="button"><svg viewBox="0 0 24 24" fill="none"><path d="M7 8h10M7 12h7M7 16h4"/><path d="M5 4h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2h-5l-2 2-2-2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/></svg><small>Notes</small></button>
                    <button class="illustration-option" type="button"><svg viewBox="0 0 24 24" fill="none"><path d="M4 12h16M12 4v16"/><path d="m16 8 4 4-4 4M8 8l-4 4 4 4"/></svg><small>Before/after</small></button>
                    <button class="illustration-option" type="button"><svg viewBox="0 0 24 24" fill="none"><circle cx="6" cy="12" r="2"/><circle cx="18" cy="6" r="2"/><circle cx="18" cy="18" r="2"/><path d="m8 11 8-4M8 13l8 4"/></svg><small>Connected</small></button>
                    <button class="illustration-option" type="button"><svg viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg><small>Email</small></button>
                    <button class="illustration-option" type="button"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8"/><path d="M12 8v8M8 12h8"/></svg><small>One place</small></button>
                </div>
                <h2 class="display">More conversations.<br>Way less mess.</h2>
                <div class="compare-grid">
                    <article class="compare before">
                        <div class="compare-label">Before MYinboxLAB</div>
                        <h3>All tabs. No clarity.</h3>
                        <ul>
                            <li><span class="bullet">&times;</span><span>Copy-pasting the same answer across five apps.</span></li>
                            <li><span class="bullet">&times;</span><span>Good leads disappearing under notifications.</span></li>
                            <li><span class="bullet">&times;</span><span>Customers waiting while your team searches for context.</span></li>
                            <li><span class="bullet">&times;</span><span>Nobody knows who is meant to reply next.</span></li>
                        </ul>
                    </article>
                    <article class="compare after">
                        <div class="compare-label">With MYinboxLAB</div>
                        <h3>One place. Total momentum.</h3>
                        <ul>
                            <li><span class="bullet">&#10003;</span><span>Smart replies handle routine questions around the clock.</span></li>
                            <li><span class="bullet">&#10003;</span><span>Every customer and channel stays in one queue.</span></li>
                            <li><span class="bullet">&#10003;</span><span>Your team gets the complete conversation history.</span></li>
                            <li><span class="bullet">&#10003;</span><span>Human takeover is one click away.</span></li>
                        </ul>
                    </article>
                </div>
            </div>
        </section>

        <section class="faq" id="faq">
            <div class="wrap">
                <div class="faq-head">
                    <span class="faq-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8Z"/><path d="M9.4 9a2.7 2.7 0 1 1 4.7 1.8c-.9.7-2.1 1.2-2.1 2.7M12 16.5h.01"/></svg></span>
                    <h2 class="display">Questions,<br>answered clearly.</h2>
                </div>
                <div class="faq-list">
                    @foreach([
                        ['Which channels can I connect?', 'MYinboxLAB brings Gmail, Instagram, WhatsApp, Facebook and Telegram conversations into one workspace. Channel availability can depend on the permissions and account type provided by each platform.'],
                        ['Do I need to know how to code?', 'No. Create your workspace, connect your accounts and configure the assistant from the dashboard. Your team can work from the inbox without writing code.'],
                        ['Will the AI sound robotic?', 'You can give the assistant your business knowledge and guidance. It uses the conversation context to reply naturally, can send shorter replies, and can hand the conversation to a person when judgment is needed.'],
                        ['Can a person take over from the AI?', 'Yes. Your team can take control of a conversation, reply manually and return it to automatic handling when appropriate.'],
                        ['Does Gmail update automatically?', 'Yes. Connected Gmail accounts are synchronized automatically in production, and Gmail notifications can trigger faster processing when the integration is configured correctly.'],
                        ['What happens when the AI does not know an answer?', 'It can ask a clarifying question, use safe general knowledge, or acknowledge the customer and flag the conversation for your team instead of inventing business-specific facts.'],
                        ['Can several teammates use the same inbox?', 'Yes. A workspace is designed to keep the team on the same customer history and make it clear which conversations need attention.'],
                    ] as [$question, $answer])
                        <article class="faq-item" x-data="{ open: false }">
                            <button class="faq-question" type="button" @click="open = !open" :aria-expanded="open"><span>{{ $question }}</span><i class="faq-toggle" aria-hidden="true"></i></button>
                            <div class="faq-answer" :class="{ 'open': open }"><div class="faq-answer-clip"><div class="faq-answer-inner">{{ $answer }}</div></div></div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Retired marketing sections kept out of the rendered page while the redesign is rebuilt. --}}
        {{-- <section class="social-proof" id="customers">
            <div class="wrap"><div class="proof-head"><div><div class="eyebrow" style="color:var(--blue)">Built for busy businesses</div><h2 class="display">Less inbox chaos.<br>More happy customers.</h2></div><div class="proof-count">One clear queue for your whole team ↘</div></div>
                <div class="testimonials">
                    <article class="quote"><blockquote>“I stopped switching between five apps just to find one customer’s story.”</blockquote><div class="person"><span class="avatar">AO</span><div><strong>Amara Okafor</strong><span>Beauty studio owner</span></div></div></article>
                    <article class="quote"><blockquote>“The AI replies like a teammate—not a robot reading a script.”</blockquote><div class="person"><span class="avatar">TB</span><div><strong>Tunde Bello</strong><span>Auto care operator</span></div></div></article>
                    <article class="quote"><blockquote>“Now we know exactly what needs a reply and who is handling it.”</blockquote><div class="person"><span class="avatar">JN</span><div><strong>Jade N.</strong><span>Online store manager</span></div></div></article>
                </div>
            </div>
        </section>

        <section class="features" id="product">
            <div class="wrap"><div class="section-center"><div class="eyebrow" style="color:var(--lime)">The unified inbox</div><h2 class="display">One queue that actually makes sense.</h2><p>See every conversation, its context and who owns the next move—without bouncing between tabs.</p></div>
                <div class="feature-stage"><div class="feature-list"><div class="feature-item active"><h3>Every channel, together</h3><p>Social DMs and email in one focused workspace.</p></div><div class="feature-item"><h3>Know what needs attention</h3><p>Useful states and filters—not a wall of alerts.</p></div><div class="feature-item"><h3>AI and humans, in sync</h3><p>Take over instantly and resume AI when you choose.</p></div></div>
                    <div class="inbox-window"><div class="window-bar"><div class="dots"><i></i><i></i><i></i></div><strong>Live workspace</strong><span style="color:#777;font-size:.75rem">Auto Parts</span></div><div class="inbox-body"><div class="conversations"><div class="inbox-search">⌕ Search conversations</div><div class="conversation active"><strong>Amaka N.</strong><p>Can I book for Saturday?</p><span class="tag">AI handling</span></div><div class="conversation"><strong>Tunde Bello</strong><p>What is included in detailing?</p><span class="tag">Needs reply</span></div><div class="conversation"><strong>Order enquiry</strong><p>Do you deliver to Ikeja?</p><span class="tag">Waiting</span></div></div><div class="chat"><div class="chat-head">Amaka N. <span style="float:right;color:var(--blue);font-size:.72rem">AI active</span></div><div class="chat-messages"><div class="bubble customer">Hi, do you offer interior detailing for a Camry?</div><div class="bubble ai"><div class="bubble-label">AI teammate</div>Yes, we do. Our interior detail covers seats, carpets and dashboard.</div><div class="bubble ai">Would you like a weekday or Saturday booking?</div><div class="bubble customer">Saturday works. Morning if possible.</div></div><div class="composer">Write a reply… <strong style="float:right;color:var(--blue)">Send</strong></div></div></div></div>
                </div>
            </div>
        </section>

        <section class="steps" id="how"><div class="wrap"><div class="steps-head"><div><div class="eyebrow" style="color:var(--lime)">Live in three steps</div><h2 class="display">From scattered to sorted. Fast.</h2></div><a class="btn btn-white" href="{{ $primaryUrl }}">Get started free →</a></div><div class="steps-grid"><article class="step"><span class="step-number">1</span><h3>Create your workspace</h3><p>Sign in with Google and tell us the basics about your business.</p></article><article class="step"><span class="step-number">2</span><h3>Connect your channels</h3><p>Bring in the accounts your customers already message every day.</p></article><article class="step"><span class="step-number">3</span><h3>Let conversations flow</h3><p>Turn on your AI teammate, watch the queue and step in whenever you want.</p></article></div></div></section>

        <section class="ai-section" id="ai"><div class="wrap ai-grid"><div class="ai-copy"><div class="eyebrow" style="color:var(--blue)">AI that knows its role</div><h2 class="display">Helpful enough to act. Smart enough to ask.</h2><p>Teach it your services, prices and policies once. It answers naturally, understands voice notes and hands sensitive decisions to your team.</p><div class="checks"><div class="check"><i>✓</i>Short, natural customer replies</div><div class="check"><i>✓</i>Business knowledge plus safe general knowledge</div><div class="check"><i>✓</i>Voice-note understanding</div><div class="check"><i>✓</i>Human takeover without awkward silence</div></div><a class="btn btn-primary" href="{{ $primaryUrl }}">Meet your AI teammate →</a></div><div class="phone-demo"><div class="phone-top"></div><div class="phone-chat"><div class="bubble customer voice"><span class="play">▶</span><span class="wave"></span><small>0:12</small></div><div class="bubble ai">Yes, we can detail your Camry this Saturday.</div><div class="bubble ai">Would morning or afternoon work better?</div><div style="padding:12px 4px 2px;color:#777;font-size:.75rem">AI understood the voice note and replied naturally ✦</div></div></div></div></section>

        <section class="final-cta"><div class="wrap"><h2 class="display">Your customers are already talking.</h2><p>Bring every conversation together and give your team room to focus on the ones that matter most.</p><div class="final-actions"><a class="btn btn-dark" href="{{ $primaryUrl }}">{{ $primaryLabel }} →</a><a class="btn" href="#product">Explore the product</a></div></div></section> --}}
    </main>

    <footer class="footer">
        <div class="wrap">
            <div class="footer-top">
                <div>
                    <div class="footer-brand"><a class="brand" href="/">MYinboxLAB</a><p>Every customer conversation, organized. Every important reply, easier to find.</p><div class="footer-social"><a href="#" aria-label="Instagram"><span class="platform-icon" data-platform-icon="instagram"></span></a><a href="#" aria-label="WhatsApp"><span class="platform-icon" data-platform-icon="whatsapp"></span></a><a href="#" aria-label="Facebook"><span class="platform-icon" data-platform-icon="facebook"></span></a><a href="#" aria-label="Gmail"><span class="platform-icon" data-platform-icon="gmail"></span></a><a href="#" aria-label="Telegram"><span class="platform-icon" data-platform-icon="telegram"></span></a></div></div>
                    <div class="footer-links" style="margin-top:58px"><div><strong>Product</strong><a href="#faq">FAQ</a><a href="#">Unified inbox</a><a href="#">AI assistant</a><a href="#">Supported channels</a></div><div><strong>Resources</strong><a href="#faq">Help centre</a><a href="#faq">Getting started</a><a href="#faq">Conversation guide</a><a href="#faq">Updates</a></div><div><strong>MYinboxLAB</strong><a href="#">Our story</a><a href="#">How it works</a><a href="#">Security</a><a href="#">Contact</a></div><div><strong>Workspace</strong>@auth<a href="{{ route('dashboard') }}">Open dashboard</a>@else<a href="{{ route('login') }}">Sign in</a><a href="{{ route('register') }}">Create workspace</a>@endauth</div></div>
                </div>
                <div class="footer-art"><img src="{{ asset('images/marketing/myinboxlab-footer-growth.png') }}" alt="Messages flowing into an organized inbox and moving upward"><div class="footer-tagline">More clarity.<br>More momentum.</div></div>
            </div>
            <div class="footer-bottom"><span>© {{ date('Y') }} <strong>MYinboxLAB</strong>. Built for better conversations.</span><span>Privacy · Terms · Security</span></div>
        </div>
    </footer>

    <footer class="footer footer-legacy"><div class="wrap"><div class="footer-top"><div class="footer-brand"><a class="brand" href="/"><span class="brand-mark">P</span><span>PERPETUAL</span></a><p>One clear workspace for every customer conversation—and an AI teammate that keeps things moving.</p></div><div class="footer-links"><div><strong>Product</strong><a href="#product">Unified inbox</a><a href="#ai">AI teammate</a><a href="#how">How it works</a></div><div><strong>Channels</strong><a href="#product">Instagram</a><a href="#product">WhatsApp</a><a href="#product">Gmail</a></div><div><strong>Account</strong>@auth<a href="{{ route('dashboard') }}">Dashboard</a>@else<a href="{{ route('login') }}">Log in</a><a href="{{ route('register') }}">Create account</a>@endauth</div></div></div><div class="footer-bottom"><span>© {{ date('Y') }} Perpetual. Built for better conversations.</span><span>Privacy · Terms · Security</span></div></div></footer>
</div>
</body>
</html>
