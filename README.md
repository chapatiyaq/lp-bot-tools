Allows bot edits to Liquipedia and the tolueno wiki with an interface using PHP/cURL

The interface is scrappy but functional.

# Usage
Since most of the operations are massive, try to separate them in multiple sub-operations, using the "From index"/"Limit" parameters.

# Cookies / Security
The log-in function is able to rely on cookies. If you choose to disable it, you may hit the log-in limit which will prevent you from logging in too much in a certain time span.

Some cookies are set by the wiki APIs after log-in queries are executed. The information contained in these cookies is passed to the user's web browser. The cookie contents are not made public.

This code does not include cookie consent warnings. Anyway, this code was mostly designed as a personal project. Someone would have to be trustful to use a website hosting such a tool without being sure about what is done with the log-in information and cookies.