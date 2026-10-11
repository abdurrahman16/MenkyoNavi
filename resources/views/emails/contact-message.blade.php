<x-mail::message>
# MenkyoNavi contact submission

**Submitted:** {{ $submittedAt }}  
**Name:** {{ $name }}  
**Email:** {{ $email }}  
**Subject:** {{ $subjectLine }}

---

{{ $body }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
