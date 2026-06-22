// EventPro demo launcher.
//
// A single, dependency-free Windows executable that boots the whole app for a
// demonstration: it ensures dependencies/assets/database exist (first run only),
// starts the Laravel server, and opens the browser. Compiled against the
// .NET Framework 4 C# compiler that ships with Windows (see build.cmd) so the
// produced Start-EventPro.exe runs on a stock machine with no extra runtime.
//
// Place Start-EventPro.exe in the project root (next to `artisan`) and run it.
using System;
using System.Diagnostics;
using System.IO;
using System.Net.Sockets;
using System.Threading;

class EventProLauncher
{
    const string Host = "127.0.0.1";
    const int Port = 8000;
    static string AppDir;
    static string Php;
    static Process Serve;

    static int Main()
    {
        Console.Title = "EventPro Launcher";
        try
        {
            AppDir = AppDomain.CurrentDomain.BaseDirectory.TrimEnd('\\');
            Banner();

            if (!File.Exists(Path.Combine(AppDir, "artisan")))
                return Fail("Could not find `artisan`.\n  Place Start-EventPro.exe in the project root (the folder that contains `artisan`).");

            Php = ResolvePhp();
            if (Php == null)
                return Fail("PHP was not found.\n  Install PHP (e.g. Laravel Herd) and make sure `php` is on your PATH.");
            Info("PHP:  " + Php);

            EnsureEnv();
            EnsureDir("vendor", "Installing PHP dependencies (composer install)...",
                      "cmd.exe", "/c composer install --no-interaction --prefer-dist");
            EnsureDir("node_modules", "Installing Node dependencies (npm install)...",
                      "cmd.exe", "/c npm install");
            EnsureDir(Path.Combine("public", "build"), "Building frontend assets (npm run build)...",
                      "cmd.exe", "/c npm run build");
            EnsureDatabase();

            StartServer();
            return WaitLoop();
        }
        catch (Exception ex)
        {
            return Fail("Unexpected error: " + ex.Message);
        }
    }

    // ---- Boot steps -------------------------------------------------------

    static void EnsureEnv()
    {
        var env = Path.Combine(AppDir, ".env");
        if (File.Exists(env)) return;

        var example = Path.Combine(AppDir, ".env.example");
        if (!File.Exists(example))
            throw new Exception("No .env and no .env.example to copy from.");

        Step("Creating .env from .env.example...");
        File.Copy(example, env);
        Run(Php, "artisan key:generate --force");
    }

    static void EnsureDir(string relative, string message, string file, string args)
    {
        if (Directory.Exists(Path.Combine(AppDir, relative))) return;
        Step(message);
        int code = Run(file, args);
        if (code != 0)
            throw new Exception(message + " failed (exit " + code + ").");
    }

    static void EnsureDatabase()
    {
        // Only sqlite needs a file created; other drivers are assumed ready.
        var sqlite = Path.Combine(AppDir, "database", "database.sqlite");
        bool fresh = false;
        if (!File.Exists(sqlite) && IsSqlite())
        {
            Step("Creating sqlite database + seeding demo data...");
            Directory.CreateDirectory(Path.GetDirectoryName(sqlite));
            File.Create(sqlite).Close();
            fresh = true;
        }
        if (fresh)
            Run(Php, "artisan migrate --force --seed");
    }

    static bool IsSqlite()
    {
        try
        {
            foreach (var line in File.ReadAllLines(Path.Combine(AppDir, ".env")))
            {
                var t = line.Trim();
                if (t.StartsWith("DB_CONNECTION="))
                    return t.Substring("DB_CONNECTION=".Length).Trim().Trim('"') == "sqlite";
            }
        }
        catch { }
        return true; // default install ships sqlite
    }

    static void StartServer()
    {
        if (PortInUse(Port))
            throw new Exception("Port " + Port + " is already in use. Stop whatever is using it and try again.");

        Step("Starting server at http://" + Host + ":" + Port + " ...");
        var psi = new ProcessStartInfo
        {
            FileName = Php,
            Arguments = "artisan serve --host=" + Host + " --port=" + Port,
            WorkingDirectory = AppDir,
            UseShellExecute = false,
        };
        Serve = Process.Start(psi);

        Console.CancelKeyPress += (s, e) => StopServer();
        AppDomain.CurrentDomain.ProcessExit += (s, e) => StopServer();

        WaitForServer();
        OpenBrowser("http://" + Host + ":" + Port);
        PrintReady();
    }

    static int WaitLoop()
    {
        if (Serve == null) return 1;
        Serve.WaitForExit();
        return Serve.ExitCode;
    }

    static void StopServer()
    {
        try { if (Serve != null && !Serve.HasExited) Serve.Kill(); }
        catch { }
    }

    // ---- Helpers ----------------------------------------------------------

    static int Run(string file, string args)
    {
        var psi = new ProcessStartInfo
        {
            FileName = file,
            Arguments = args,
            WorkingDirectory = AppDir,
            UseShellExecute = false,
        };
        var p = Process.Start(psi);
        p.WaitForExit();
        return p.ExitCode;
    }

    static string ResolvePhp()
    {
        var onPath = Which("php.exe");
        if (onPath != null) return onPath;
        var herd = Path.Combine(
            Environment.GetFolderPath(Environment.SpecialFolder.UserProfile),
            ".config", "herd-lite", "bin", "php.exe");
        return File.Exists(herd) ? herd : null;
    }

    static string Which(string exe)
    {
        var path = Environment.GetEnvironmentVariable("PATH") ?? "";
        foreach (var dir in path.Split(';'))
        {
            if (string.IsNullOrWhiteSpace(dir)) continue;
            try
            {
                var full = Path.Combine(dir.Trim(), exe);
                if (File.Exists(full)) return full;
            }
            catch { }
        }
        return null;
    }

    static bool PortInUse(int port)
    {
        try
        {
            using (var c = new TcpClient())
            {
                var ok = c.BeginConnect(Host, port, null, null).AsyncWaitHandle.WaitOne(300);
                return ok && c.Connected;
            }
        }
        catch { return false; }
    }

    static void WaitForServer()
    {
        for (int i = 0; i < 40; i++) // up to ~10s
        {
            if (PortInUse(Port)) return;
            Thread.Sleep(250);
        }
    }

    static void OpenBrowser(string url)
    {
        try { Process.Start(new ProcessStartInfo(url) { UseShellExecute = true }); }
        catch { }
    }

    // ---- Console output ---------------------------------------------------

    static void Banner()
    {
        Console.ForegroundColor = ConsoleColor.Cyan;
        Console.WriteLine();
        Console.WriteLine("  ===================================");
        Console.WriteLine("   EventPro  -  demo launcher");
        Console.WriteLine("  ===================================");
        Console.ResetColor();
        Console.WriteLine();
    }

    static void Step(string m)
    {
        Console.ForegroundColor = ConsoleColor.Yellow;
        Console.WriteLine("> " + m);
        Console.ResetColor();
    }

    static void Info(string m) { Console.WriteLine("  " + m); }

    static void PrintReady()
    {
        Console.WriteLine();
        Console.ForegroundColor = ConsoleColor.Green;
        Console.WriteLine("  EventPro is running:  http://" + Host + ":" + Port);
        Console.ResetColor();
        Console.WriteLine();
        Console.WriteLine("  Demo logins (password: password):");
        Console.WriteLine("    Super admin   admin@eventpro.io");
        Console.WriteLine("    Tenant admin  nuwan@mangala.lk");
        Console.WriteLine("    Staff         sanduni@mangala.lk");
        Console.WriteLine();
        Console.WriteLine("  Mail is logged to storage/logs (MAIL_MAILER=log).");
        Console.WriteLine("  Press Ctrl+C or close this window to stop the server.");
        Console.WriteLine();
    }

    static int Fail(string m)
    {
        Console.ForegroundColor = ConsoleColor.Red;
        Console.WriteLine();
        Console.WriteLine("  ERROR: " + m);
        Console.ResetColor();
        Console.WriteLine();
        Console.WriteLine("  Press any key to close...");
        try { Console.ReadKey(true); } catch { }
        return 1;
    }
}
