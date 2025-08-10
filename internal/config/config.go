package config

import (
	"errors"
	"fmt"
	"os"
	"path/filepath"

	ini "gopkg.in/ini.v1"
)

type Config struct {
	// [Config]
	HttpListener           bool
	HttpApi                bool
	HttpVioletApi          bool
	XmppListener           bool
	RealHttpRoot           string
	HttpRoot               string
	HttpPluginsFolder      string
	StandAloneAuthBypass   bool
	AllowAnonymousRegistration bool
	AllowUserManageBunny   bool
	AllowUserManageZtamp   bool
	SessionTimeout         int
	TTS                    string
	MaxNumberOfBunnies     int
	MaxBurstNumberOfBunnies int

	OpenJabNabServers struct {
		PingServer         string
		BroadServer        string
		XmppServer         string
		ListeningHttpPort  int
		ListeningXmppPort  int
	}

	Log struct {
		LogFile        string
		LogFileLevel   string
		LogScreenLevel string
		DisplayCronLog bool
		NetworkDump    bool
	}

    Accounts struct {
        Username string
        Password string
    }

    Auth struct {
        Bypass bool
    }

    // Directory for per-plugin INI files (plugin_<name>.ini). Defaults to /config/plugins in containers.
    PluginsDir string
}

func LoadDefault() (*Config, error) {
	// Try environment variable OJN_CONFIG, then server/openjabnab.ini, then server/openjabnab.ini-dist
	candidates := []string{}
	if v := os.Getenv("OJN_CONFIG"); v != "" {
		candidates = append(candidates, v)
	}
    // Container-friendly defaults
    candidates = append(candidates,
        filepath.Join("/config", "openjabnab.ini"),
        filepath.Join("/config", "openjabnab.ini-dist"),
    )
	candidates = append(candidates,
		filepath.Join("server", "openjabnab.ini"),
		filepath.Join("server", "openjabnab.ini-dist"),
	)
    var firstErr error
    for _, path := range candidates {
        cfg, err := Load(path)
        if err == nil {
            return cfg, nil
        }
        if firstErr == nil { firstErr = err }
    }
    if firstErr == nil {
        firstErr = errors.New("no config file found")
    }
    return nil, firstErr
}

func Load(path string) (*Config, error) {
	f, err := ini.Load(path)
	if err != nil {
		return nil, fmt.Errorf("load ini: %w", err)
	}
	c := &Config{}
	// [Config]
	sec := f.Section("Config")
	c.HttpListener = sec.Key("httpListener").MustBool(true)
	c.HttpApi = sec.Key("httpApi").MustBool(true)
	c.HttpVioletApi = sec.Key("httpVioletApi").MustBool(true)
	c.XmppListener = sec.Key("xmppListener").MustBool(true)
	c.RealHttpRoot = sec.Key("RealHttpRoot").MustString("../../http-wrapper/ojn_local/")
	c.HttpRoot = sec.Key("HttpRoot").MustString("ojn_local")
	c.HttpPluginsFolder = sec.Key("HttpPluginsFolder").MustString("plugins")
	c.StandAloneAuthBypass = sec.Key("StandAloneAuthBypass").MustBool(false)
	c.AllowAnonymousRegistration = sec.Key("AllowAnonymousRegistration").MustBool(false)
	c.AllowUserManageBunny = sec.Key("AllowUserManageBunny").MustBool(false)
	c.AllowUserManageZtamp = sec.Key("AllowUserManageZtamp").MustBool(false)
	c.SessionTimeout = sec.Key("SessionTimeout").MustInt(300)
	c.TTS = sec.Key("TTS").MustString("acapela")
	c.MaxNumberOfBunnies = sec.Key("MaxNumberOfBunnies").MustInt(64)
	c.MaxBurstNumberOfBunnies = sec.Key("MaxBurstNumberOfBunnies").MustInt(72)

	// [OpenJabNabServers]
	srv := f.Section("OpenJabNabServers")
	c.OpenJabNabServers.PingServer = srv.Key("PingServer").MustString("my.domain.com")
	c.OpenJabNabServers.BroadServer = srv.Key("BroadServer").MustString("my.domain.com")
	c.OpenJabNabServers.XmppServer = srv.Key("XmppServer").MustString("my.domain.com")
	c.OpenJabNabServers.ListeningHttpPort = srv.Key("ListeningHttpPort").MustInt(8080)
	c.OpenJabNabServers.ListeningXmppPort = srv.Key("ListeningXmppPort").MustInt(5222)

	// [Log]
	log := f.Section("Log")
	c.Log.LogFile = log.Key("LogFile").MustString("openjabnab.log")
	c.Log.LogFileLevel = log.Key("LogFileLevel").MustString("Debug")
	c.Log.LogScreenLevel = log.Key("LogScreenLevel").MustString("Warning")
	c.Log.DisplayCronLog = log.Key("DisplayCronLog").MustBool(false)
	c.Log.NetworkDump = log.Key("NetworkDump").MustBool(true)

    // [Accounts] optional demo/test user
    acc := f.Section("Accounts")
    c.Accounts.Username = acc.Key("Username").MustString("")
    c.Accounts.Password = acc.Key("Password").MustString("")

    // [Auth]
    auth := f.Section("Auth")
    c.Auth.Bypass = auth.Key("Bypass").MustBool(false)

    // PluginsDir: env var takes precedence; default to /config/plugins, fallback to ./plugins
    if v := os.Getenv("OJN_PLUGIN_DIR"); v != "" {
        c.PluginsDir = v
    } else {
        c.PluginsDir = "/config/plugins"
        if _, err := os.Stat(c.PluginsDir); err != nil {
            c.PluginsDir = "plugins"
        }
    }
	return c, nil
}
